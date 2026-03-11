<?php
declare(strict_types=1);
/**
 * umask dient als Set von Bits, die von den Standard-Berechtigungen entfernt werden
 * 777 mit umask(002) wird zu 775
 */
umask(0002);

// ------------------------------------------------------------
// PHP-Version prüfen
// ------------------------------------------------------------
if (PHP_VERSION_ID < 80000) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Dieses System benötigt mindestens PHP > 8.1. Aktuell läuft: " . PHP_VERSION;
    exit(1);
}

// ------------------------------------------------------------
// Error Reporting
// ------------------------------------------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

define("ROOT", dirname(__DIR__) . DIRECTORY_SEPARATOR);
ini_set('error_log', ROOT . 'logs/' . date('Y-m-d') . '.php_error.log');

setlocale(LC_TIME, "de_DE.utf8");
date_default_timezone_set("Europe/Berlin");

$isCli = (PHP_SAPI === 'cli');

// ------------------------------------------------------------
// Session Handling
// ------------------------------------------------------------
if (!$isCli) {
    session_name('TEMPLATE_SESSID');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ------------------------------------------------------------
// Config laden
// ------------------------------------------------------------
$ini_root_link = ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini';

if (!$isCli && !is_file($ini_root_link)) {
    header('Location: setup.php');
    exit;
}

$ini = parse_ini_file($ini_root_link, false, INI_SCANNER_TYPED) ?: [];
foreach ($ini as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Request URI fallback (z.B. für CLI Server)
if (!$isCli && empty($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] ?? '';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

// ------------------------------------------------------------
// Admin-Only Dateien
// ------------------------------------------------------------
$adminOnly = ['config.php', 'phpinfo.php', 'setup.php'];

// ------------------------------------------------------------
// Includes (Basis-Klassen & Funktionen)
// ------------------------------------------------------------
require_once ROOT . "inc/autoload.php";

// ------------------------------------------------------------
// Guest Login Handling
// ------------------------------------------------------------
$loggedIn = user::isLoggedIn();

if (!$isCli && !$loggedIn) {
    $guestSites = [
        'login.php',
        'index.php',
        'setup.php',
        '/api/', // ganzer Ordner für Gäste
        '/cronjobs/'
    ];

    $requestPath = $_SERVER['REQUEST_URI'];
    $isGuestUrl  = false;

    foreach ($guestSites as $guestEntry) {
        if ($guestEntry[0] === '/') {
            // Ordner oder kompletter Pfad
            if (str_contains($requestPath, $guestEntry)) {
                $isGuestUrl = true;
                break;
            }
        } else {
            // Dateiname prüfen
            if (
                str_ends_with($requestPath, '/' . $guestEntry) ||
                str_contains($requestPath, '/' . $guestEntry . '?')
            ) {
                $isGuestUrl = true;
                break;
            }
        }
    }

    if (!$isGuestUrl) {
        header('Location: index.php');
        exit;
    }
}

// ------------------------------------------------------------
// DB-Connector laden
// ------------------------------------------------------------
foreach (glob(ROOT . "inc/db/*.php") as $filename) {
    include_once $filename;

    $log = new Log();
}

try {
    $connection     = false;
    $connectionType = defined('MSSQL_TYP') ? MSSQL_TYP : 'MSSQL';

    $MSSQL_SERVER = defined('MSSQL_SERVER') ? MSSQL_SERVER : '';
    $MSSQL_PORT   = defined('MSSQL_PORT') ? MSSQL_PORT : '';
    $MSSQL_DB     = defined('MSSQL_DB') ? MSSQL_DB : '';
    $MSSQL_USER   = defined('MSSQL_USER') ? MSSQL_USER : '';
    $MSSQL_PASS   = defined('MSSQL_PASS') ? MSSQL_PASS : '';

    switch ($connectionType) {
        case DbConnector::CONNECTION_MSSQL:
            // Update MSSQL connection settings
            $ENCRYPT                = defined('MSSQL_ENCRYPT') ? MSSQL_ENCRYPT : false;
            $TRUSTSERVERCERTIFICATE = defined('MSSQL_TRUSTSERVERCERTIFICATE') ? MSSQL_TRUSTSERVERCERTIFICATE : false;

            $connection = new MssqlConnector($MSSQL_SERVER, $MSSQL_DB, $MSSQL_USER, $MSSQL_PASS, $MSSQL_PORT, 'utf-8', true, null, $ENCRYPT, $TRUSTSERVERCERTIFICATE);
            $sqlErrors  = sqlsrv_errors();
            break;
        case DbConnector::CONNECTION_MYSQL:
            $connection = new MysqlConnector($MSSQL_SERVER, $MSSQL_DB, $MSSQL_USER, $MSSQL_PASS, $MSSQL_PORT);
            $sqlErrors  = mysqli_connect_error();
            break;
        case DbConnector::CONNECTION_ODBC:
            $connection = new OdbcConnector($MSSQL_SERVER, $MSSQL_DB, $MSSQL_USER, $MSSQL_PASS, SQL_CUR_USE_DRIVER);
            $sqlErrors  = odbc_errormsg();
            break;
        default:
            $sqlErrors = "Kein gültiger DB Typ in der Config angegeben: " . $connectionType;
    }

    if ($connection === false) {
        Notification::error("Fehler bei DB Verbindung<br><pre>" . print_r($sqlErrors, true) . "</pre>");
        $log->log("Fehler bei DB Verbindung", Log::LEVEL_ERROR, $sqlErrors);
    }
} catch (Throwable $e) {
    Notification::error("Fehler bei DB Verbindung: " . $e->getMessage());
    $log->log("Fehler bei DB Verbindung: " . $e->getMessage(), Log::LEVEL_ERROR, $e->getTraceAsString());
}

// ------------------------------------------------------------
// Config aus DB laden
// ------------------------------------------------------------
$arr_config = config::getConfigs();
$arr_system = array();
// wenn Parashift Proxy genutzt werden soll, muss parashift endpunkt geändert werden
foreach ($arr_config as $cfg) {
    $arr_system[$cfg['key']] = $cfg['value'];
}

// ------------------------------------------------------------
// Klassen dynamisch laden
// ------------------------------------------------------------
foreach (glob(ROOT . "inc/source/class_*.php") as $filename) {
    include_once $filename;
}
foreach (glob(ROOT . "inc/extension/class_*.php") as $filename) {
    include_once $filename;
}

// ------------------------------------------------------------
// User Context laden
// ------------------------------------------------------------
if (!$isCli && $loggedIn) {
    $obj_user = user::getLoggedInUser();

    $obj_firma = new firma();
    if ($obj_firma->load_by_number($obj_user->mandant) && !empty($obj_firma->name)) {
        user::setValuesToUser(['mandant_name' => $obj_firma->name]);

        $obj_filiale = new filiale();
        if ($obj_filiale->load_by_number($obj_user->filiale, $obj_firma->ID) && !empty($obj_filiale->name)) {
            user::setValuesToUser(['filiale_name' => $obj_filiale->name]);

            $obj_hersteller = new hersteller($obj_filiale->hersteller_id);
            if (!empty($obj_hersteller->name)) {
                user::setValuesToUser([
                                          'hersteller_name'  => $obj_hersteller->name,
                                          'hersteller_logo'  => $obj_hersteller->logo,
                                          'hersteller_color' => $obj_hersteller->color,
                                          'hersteller_css'   => $obj_hersteller->css
                                      ]);
            }
        }
    }
}
