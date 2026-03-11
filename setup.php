<?php
/**
 * First, it sets several configuration options for error handling, such as turning on the display of errors and
 * logging errors. It also sets the locale (LC_TIME) to de_DE.utf8 (German) and the time zone to Europe/Berlin.
 */
ini_set('display_errors', 'on');
ini_set('log_errors', 'on');
ini_set('display_startup_errors', 'on');
// Alles melden, aber Deprecated-Warnungen ausblenden:
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
setlocale(LC_TIME, "de_DE.utf8");
date_default_timezone_set("Europe/Berlin");

/**
 * Then it defines some constants. 'ROOT' refers to the script's directory, 'PROJECT_NAME' refers to the name of the
 * directory, and 'VERSION_NR' is set to 'Setup'.
 */
define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('PROJECT_PATH', basename(__DIR__));
define('PROJECT_NAME', basename(__DIR__));
define('VERSION_NR', 'Setup');
session_name("SCANHUBSETUP");
session_start();

/**
 * Then, it checks if a file named 'config.ini' exists in the 'sicher' subdirectory of 'ROOT'. If such a file exists,
 * it redirects the user to 'index.php' and terminates the script.
 */
if (file_exists(ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini')) {
    header('Location: index.php');
    exit;
}

/**
 * Afterwards, it starts a process session, includes several files from the 'inc' directory such as function definitions
 * and class definitions.
 */
include_once ROOT . "inc/functions.php";
require_once ROOT . "inc/class_base.php";

include_once ROOT . "inc/db/DatabaseException.php";
include_once ROOT . "inc/db/ServerInfo.php";
include_once ROOT . "inc/db/DbConnector.php";
include_once ROOT . "inc/db/MssqlConnector.php";
include_once ROOT . "inc/db/MysqlConnector.php";

require_once ROOT . "inc/auth/class_firma.php";
require_once ROOT . "inc/auth/class_filiale.php";
require_once ROOT . "inc/auth/class_hersteller.php";
require_once ROOT . "inc/auth/class_user.php";

include_once ROOT . "inc/class_config.php";
include_once ROOT . "inc/class_notification.php";
include_once ROOT . "inc/class_log.php";

/**
 * Setup-Defaults laden (nur für setup.php):
 * - liest sicher/ini/default.ini
 * - befüllt $_POST nur dort, wo noch keine Eingabe vorhanden ist
 */
$defaultIni = ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'default.ini';
if (is_file($defaultIni)) {
    $defaults = parse_ini_file($defaultIni, true, INI_SCANNER_TYPED);
    if (is_array($defaults)) {
        foreach ($defaults as $section => $values) {
            if (!is_array($values)) {
                continue;
            }
            if (!isset($_POST[$section]) || !is_array($_POST[$section])) {
                $_POST[$section] = [];
            }
            foreach ($values as $k => $v) {
                if (!array_key_exists($k, $_POST[$section]) || $_POST[$section][$k] === '' || $_POST[$section][$k] === null) {
                    $_POST[$section][str_replace($section.'_', '', strtolower($k))] = $v;
                }
            }
        }
    }
}

$arr_presumption = array('nav', 'error', 'message', 'action');
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}

/**
 * It sets a variable $nav to 'setup', which seems to be used somewhere else in the code not shown here.
 */
$nav       = 'setup';
$errors    = array();
$loggedIn  = false;
$mandatory = array(
        'mssql'   => array(
                'typ'    => 'SQL-Server - Verbindung',
                'server' => 'SQL-Server - Server',
                'user'   => 'SQL-Server - User',
                'pass'   => 'SQL-Server - Passwort',
                'db'     => 'SQL-Server - Datenbank',
        ),
        'login'   => array(
                'user' => 'Admin-Login - User',
                'pass' => 'Admin-Login - Passwort'
        ),
        'company' => array(
                'name'    => 'Firma',
                'account' => 'Konto',
        ),
        'branch'  => array(
                'name' => 'Filiale',
        )
);

$breadcrumbs = [
        ['title' => 'Startseite', 'url' => 'index.php'],
        ['title' => 'Setup', 'url' => 'setup.php']
];

switch($action) {
    case 'update':
        if (!empty($_POST)) {
            foreach ($mandatory as $fieldGroup => $fields) {
                foreach ($fields as $field => $fieldName) {
                    if (empty($_POST[$fieldGroup][$field])) {
                        $errors[] = $fieldName . " ist ein Pflichtfeld";
                    }
                }
            }
            if (empty($errors)) {
                //Die Validierung der Pflichtfelder war erfolgreich
                $checkDb = checkDb($_POST['mssql']);
                if ($checkDb !== false && !is_array($checkDb)) {
                    $connection = $checkDb;
                    if (empty($errors)) {
                        unset($_POST['action']);
                        write_php_ini($_POST, ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini');

                        foreach ($_POST as $key => $val) {
                            if (is_array($val)) {
                                foreach ($val as $skey => $sval) {
                                    // nur Datenbank
                                    if (strtoupper($key == 'MSSQL')) {
                                        unset($_POST[$key][$skey]);
                                    } else {
                                        $res[] = $key . " = " . (is_numeric($sval) ? $sval : "'" . $sval . "'");
                                    }
                                }
                            } else {
                                $res[] = $key . " = " . (is_numeric($val) ? $val : "'" . $val . "'");
                            }
                        }
                        // zusammenfassen
                        $res = implode("\r\n", $res);
                        /**
                         * Firma erstellen
                         */
                        $obj_firma = new firma();
                        $arr_post  = array(
                                'name'    => $_POST['company']['name'],
                                'account' => $_POST['company']['account'],
                                'number'  => $_POST['company']['number'],
                        );
                        $obj_firma->set_vars($arr_post);
                        $error = $obj_firma->check_data();
                        if (empty($error)) {
                            $obj_firma->insert();
                        }
                        /**
                         * Filiale erstellen
                         */
                        $obj_filiale = new filiale();
                        $arr_post    = array(
                                'name'     => $_POST['branch']['name'],
                                'number'   => $_POST['branch']['number'],
                                'firma_id' => $obj_firma->ID,
                        );
                        $obj_filiale->set_vars($arr_post);
                        $error = $obj_filiale->check_data();
                        if (empty($error)) {
                            $obj_filiale->insert();
                        }
                        /**
                         * Benutzer erstellen
                         */
                        include "sicher/data.php";
                        $user = user::createUser($_POST['login']['user'], $_POST['login']['pass'], '01', '01');
                        if ($user !== false) {
                            $groups = array('admin');
                            $user->setVars(['userGroups' => json_encode(array_values($groups), JSON_UNESCAPED_UNICODE)]);
                            $user->saveToDb();
                            Notification::info('Benutzer erfolgreich erstellt');
                        } else {
                            Notification::error('Fehler beim Erstellen des Administrators');
                        }

                        header('Location: index.php');
                        die;
                    }
                }
                if (is_array($checkDb)) {
                    $errors = array_merge($errors, $checkDb);
                }
            }
        }
        break;
}

/**
 * @param $databaseInfo
 *
 * @return array|false|DbConnector
 */
function checkDb($databaseInfo)
{
    $errors = [];
    try {
        $sqlErrors  = null;
        $connection = false;
        switch ($databaseInfo['typ']) {
            case DbConnector::CONNECTION_MSSQL:
                $connection = $connection = new MssqlConnector($databaseInfo['server'], $databaseInfo['db'], $databaseInfo['user'], $databaseInfo['pass'], $databaseInfo['port']);
                $sqlErrors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
                if (!empty($sqlErrors)) {
                    $errors[] = "<pre>" . print_r($sqlErrors, true) . "</pre>";
                }
                break;
            case DbConnector::CONNECTION_MYSQL:
                $connection = new MysqlConnector($databaseInfo['server'], $databaseInfo['db'], $databaseInfo['user'], $databaseInfo['pass'], $databaseInfo['port']);
                $sqlErrors  = mysqli_connect_error();
                if (!empty($sqlErrors)) {
                    $errors[] = "<pre>" . $sqlErrors . "</pre>";
                }
                break;
            default:
                $errors[] = "Kein gültiger DB Typ im Setup angegeben: " . $databaseInfo['typ'];
        }
        if ($connection === false) {
            $errors[] = "Fehler bei DB Verbindung";
        }
    } catch (DatabaseException $e) {
        $errors[] = "Fehler bei DB Verbindung: <br><pre>" . print_r($e->getDatabaseError(), true) . "</pre>";
    } catch (Exception $e) {
        $errors[] = "Fehler bei DB Verbindung: " . $e->getMessage();
    }
    if (empty($errors) && !empty($connection)) {
        return $connection;
    }

    return $errors;
}

?>
<!doctype html>
<html lang="en">
<head>
    <?php include 'tmpl/head.php' ?>
</head>
<body>
<?php include 'tmpl/titlebar.php' ?>
<?php
if (!defined('VERSION_TYP') || empty(VERSION_TYP)) {
    include 'tmpl/navbar.php';
} else {
    include 'tmpl/navbar-' . VERSION_TYP . '.php';
}
?>
<section class="content">
    <div class="container-fluid">
        <form method="POST" action="setup.php" name="frm_config">
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header bg-dark text-white">System</div>
                        <div class="card-body">
                            <p><strong>Grundlegende Systemeinstellungen</strong></p>
                            <div class="mb-3">
                                <label class="form-label" for="project_name">Projekt-Name</label>
                                <input type="text" class="form-control" id="project_name" name="project[name]"
                                       value="<?php echo $_POST['project']['name'] ?? PROJECT_NAME ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="version_nr">Version</label>
                                <input type="text" class="form-control" id="version_nr" name="version[nr]"
                                       value="<?php echo $_POST['version']['nr'] ?? VERSION_NR ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="project_path">Projekt-Pfad</label>
                                <input type="text" class="form-control" id="project_path" name="project[path]"
                                       value="<?php echo $_POST['project']['path'] ?? PROJECT_PATH ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="project_root">Server-Root</label>
                                <input type="text" class="form-control" id="project_root" name="project[root]"
                                       value="<?php echo $_POST['project']['root'] ?? ROOT ?>" readonly>
                            </div>
                            <hr/>
                            <?php if (is_file($defaultIni)) { ?>
                                <p>Zeigt die Default-Konfiguration aus <code>sicher/ini/default.ini</code></p>
                                <div class="mb-3">
                                    <a href="#"
                                       class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                       data-bs-target="#defaultIniModal">
                                        Default-Konfiguration
                                    </a>
                                </div>
                                <?php
                                include 'tmpl/modal/setup_default_configuration.php';
                                ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header bg-dark text-white">SQL-Server Verbindungsangaben</div>
                        <div class="card-body">
                            <p><strong>Einstellung der Verbindung zu Datenbank.</strong></p>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_typ">Verbindung zu Datenbanktyp<span
                                            class="text-danger">*</span></label>
                                <select class="form-select" id="mssql_typ" name="mssql[typ]">
                                    <option value="MSSQL" <?php echo $_POST['mssql']['typ'] ?? 'MSSQL' ? 'selected' : '' ?>>
                                        MS-SQL
                                    </option>
                                    <option value="MYSQL" <?php echo $_POST['mssql']['typ'] ?? 'MYSQL' ? 'selected' : '' ?>>
                                        MySQL/MariaDB
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_server">Server<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mssql_server" name="mssql[server]" required
                                       placeholder="SERVER/INSTANZ"
                                       value="<?php echo $_POST['mssql']['server'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_port">Port<br><small>Standardwerte: 1433 (MSSQL) /
                                        3306
                                        (Mysql)</small></label>
                                <input type="text" class="form-control" id="mssql_port" name="mssql[port]"
                                       placeholder="1433" value="<?php echo $_POST['mssql']['port'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_user">Benutzer<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mssql_user" name="mssql[user]" required
                                       placeholder="sa" value="<?php echo $_POST['mssql']['user'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_pass">Passwort<span
                                            class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="mssql_pass" name="mssql[pass]" required
                                       value="<?php echo $_POST['mssql']['pass'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_db">Datenbank<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mssql_db" name="mssql[db]" required
                                       placeholder="DATENBANK" value="<?php echo $_POST['mssql']['db'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header bg-dark text-white">Kundeninfo</div>
                        <div class="card-body">
                            <p><strong>Installation für Kunden</strong></p>
                            <input type="hidden" name="company[number]" value="01"/>
                            <input type="hidden" name="branch[number]" value="01"/>
                            <div class="mb-3">
                                <label class="form-label" for="company_account">Kundennummer<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                       id="company_account"
                                       required="required"
                                       name="company[account]"
                                       placeholder="Kundennummer"
                                       value="<?php echo $_POST['company']['account'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="company_name">Firma<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                       id="company_name"
                                       required="required"
                                       name="company[name]"
                                       placeholder="Firma"
                                       value="<?php echo $_POST['company']['name'] ?? '' ?>">
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="branch_name">Filiale<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                       id="branch_name"
                                       required="required"
                                       name="branch[name]"
                                       placeholder="Filiale"
                                       value="<?php echo $_POST['branch']['name'] ?? '' ?>">
                            </div>
                            <p><strong>Login für Systemadministrator</strong></p>
                            <div class="mb-3">
                                <label class="form-label" for="login_user">User<span
                                            class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="login_user" required name="login[user]"
                                       value="<?php echo $_POST['login']['user'] ?? 'sa' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="login_pass">Passwort<span
                                            class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="login_pass" required name="login[pass]"
                                       value="<?php echo $_POST['login']['pass'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                </div>
                <div class="col">
                    <div class="card mt-3">
                        <div class="card-header bg-success text-white">Action</div>
                        <div class="card-body">
                            <button class="btn btn-outline-success btn-block" type="submit" name="action"
                                    value="update">Setup Speichern
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col">
                </div>
            </div>
        </form>
    </div>
</section>
<section class="content-footer"></section>
<?php include 'tmpl/foot.php' ?>
<script type="text/javascript">
    var msnry = new Masonry('#cards-row', {
        itemSelector: '.col',
        percentPosition: true
    });
</script>
</body>
</html>
