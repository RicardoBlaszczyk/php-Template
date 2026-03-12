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
session_name("INSTALLSETUP");
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

if (($_GET['action'] ?? '') === 'check_db') {
    header('Content-Type: application/json; charset=utf-8');

    $databaseInfo = $_POST['mssql'] ?? [];
    $checkDb      = checkDb($databaseInfo);

    if ($checkDb !== false && !is_array($checkDb)) {
        echo json_encode([
                                 'success' => true,
                                 'message' => 'Datenbankverbindung erfolgreich hergestellt.'
                         ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
                                 'success' => false,
                                 'message' => is_array($checkDb) ? implode('<br>', $checkDb) : 'Verbindung fehlgeschlagen.'
                         ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

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
                    $_POST[$section][str_replace($section . '_', '', strtolower($k))] = $v;
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

switch ($action) {
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
                        <div class="card-header bg-dark text-white">
                            <h4 class="modal-title">System</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Grundlegende Systemeinstellungen</strong></p>
                            <div class="mb-3">
                                <label class="form-label" for="project_name">Projekt-Name</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-terminal"></i></span>
                                    <input type="text" class="form-control" id="project_name" name="project[name]"
                                           value="<?php echo $_POST['project']['name'] ?? PROJECT_NAME ?>">
                                </div>
                                <div class="form-text">Der Projektname wird für die Titelleiste verwendet.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="version_nr">Version</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-tag"></i></span>
                                    <input type="text" class="form-control" id="version_nr" name="version[nr]"
                                           value="<?php echo $_POST['version']['nr'] ?? VERSION_NR ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="project_path">Pfad relativ zum Webserver-Root</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-bookmark-o"></i></span>
                                    <input type="text" class="form-control" id="project_path" name="project[path]"
                                           value="<?php echo $_POST['project']['path'] ?? PROJECT_PATH ?>">
                                </div>
                                <div class="form-text">Gib den Projektpfad relativ zum Webserver-Root an, z. B.
                                    /projektname.
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="project_root">Server-Root</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-folder-open-o"></i></span>

                                    <input type="text" class="form-control" id="project_root" name="project[root]"
                                           value="<?php echo $_POST['project']['root'] ?? ROOT ?>" readonly>
                                </div>
                                <div class="form-text">Der Server-Root wird automatisch ermittelt.</div>
                            </div>

                            <?php if (is_file($defaultIni)) { ?>
                                <p><strong>Default-Konfiguration</strong></p>
                                <p>Zeigt die Default-Konfiguration aus <code>default.ini</code> an, sofern die Datei
                                    vorhanden ist.</p>
                                <div class="mb-3">
                                    <a href="#"
                                       class="btn btn-outline-secondary btn-sm mb-3" data-bs-toggle="modal"
                                       data-bs-target="#defaultIniModal">
                                        Default-Konfiguration anzeigen
                                    </a>
                                    <div class="form-text">Wird nur geladen, wenn die Datei
                                        <code>sicher/ini/default.ini</code> vorhanden ist.
                                    </div>
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
                        <div class="card-header bg-dark text-white">
                            <h4 class="modal-title">SQL-Server Verbindungsangaben</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Einstellung der Verbindung zu Datenbank.</strong></p>
                            <!-- Basic switch -->
                            <div class="form-check form-switch mb-3">
                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                <input type="hidden" name="mssql[use]"
                                       value="0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="mssql_use"
                                       name="mssql[use]"
                                       value="<?php echo !empty($_POST['mssql']['use']) ? 1 : 0; ?>"
                                        <?php echo !empty($_POST['mssql']['use']) ? 'checked' : ''; ?>
                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                <label class="form-check-label"
                                       for="mssql_use">Datenbankverbindung nutzen</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mssql_typ">Verbindung zu Datenbanktyp<span
                                            class="text-danger">*</span></label><br/>

                                <?php $selectedType = $_POST['mssql']['typ'] ?? 'MSSQL'; ?>

                                <input type="radio"
                                       class="btn-check"
                                       name="mssql[typ]"
                                       id="mssql-typ-mssql"
                                       value="MSSQL" autocomplete="off"
                                        <?php echo $selectedType === 'MSSQL' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="mssql-typ-mssql">MSSQL</label>

                                <input type="radio"
                                       class="btn-check"
                                       name="mssql[typ]"
                                       id="mssql-typ-mysql"
                                       value="MYSQL"
                                       autocomplete="off"
                                        <?php echo $selectedType === 'MYSQL' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="mssql-typ-mysql">MySQL/MariaDB</label>

                                <input type="radio"
                                       class="btn-check"
                                       name="mssql[typ]"
                                       id="mssql-typ-odbc"
                                       value="ODBC"
                                       autocomplete="off"
                                        <?php echo $selectedType === 'ODBC' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="mssql-typ-odbc">ODBC</label>

                            </div>
                            <div class="row mb-3">
                                <div class="col-9">
                                    <label class="form-label" for="mssql_server">Server<span
                                                class="text-danger">*</span></label>
                                    <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-server"></i></span>
                                        <input type="text" class="form-control" id="mssql_server" name="mssql[server]"
                                               required
                                               placeholder="SERVER/INSTANZ"
                                               value="<?php echo $_POST['mssql']['server'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label" for="mssql_port">Port<br></label>
                                    <input type="text" class="form-control" id="mssql_port" name="mssql[port]"
                                           placeholder="1433" value="<?php echo $_POST['mssql']['port'] ?? '' ?>">
                                </div>
                                <div class="form-text">Standardwerte Post: 1433 (MSSQL) /
                                    3306
                                    (Mysql)
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="mssql_user">Datenbank-Benutzer<span
                                                class="text-danger">*</span></label>
                                    <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" id="mssql_user" name="mssql[user]"
                                               required
                                               placeholder="sa" value="<?php echo $_POST['mssql']['user'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="mssql_pass">Datenbank-Passwort<span
                                                class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="mssql_pass" name="mssql[pass]"
                                           required
                                           value="<?php echo $_POST['mssql']['pass'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-8">
                                    <label class="form-label" for="mssql_db">Datenbank<span
                                                class="text-danger">*</span></label>
                                    <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-database"></i></span>
                                        <input type="text" class="form-control" id="mssql_db" name="mssql[db]" required
                                               placeholder="DATENBANK"
                                               value="<?php echo $_POST['mssql']['db'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">&nbsp;
                                        <input type="hidden" id="mssql_check" name="mssql[check]" required
                                               value="<?php echo $_POST['mssql']['check'] ?? 'error' ?>">
                                    </label><br/>
                                    <button type="button" id="mssql_check_btn" class="btn btn-outline-secondary w-100">
                                        Verbindung prüfen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header bg-dark text-white">
                            <h4 class="modal-title">Kundeninfo</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Installation für Kunden</strong></p>

                            <input type="hidden" name="company[number]" value="01"/>
                            <input type="hidden" name="branch[number]" value="01"/>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="company_account">Kundennummer<span
                                                class="text-danger">*</span></label>
                                    <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-hashtag"></i></span>
                                        <input type="text" class="form-control"
                                               id="company_account"
                                               required="required"
                                               name="company[account]"
                                               placeholder="Kundennummer"
                                               value="<?php echo $_POST['company']['account'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-6">

                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="company_name">Firma<span
                                            class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-industry"></i></span>
                                    <input type="text" class="form-control"
                                           id="company_name"
                                           required="required"
                                           name="company[name]"
                                           placeholder="Firma"
                                           value="<?php echo $_POST['company']['name'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="branch_name">Filiale<span
                                            class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-home"></i></span>
                                    <input type="text" class="form-control"
                                           id="branch_name"
                                           required="required"
                                           name="branch[name]"
                                           placeholder="Filiale"
                                           value="<?php echo $_POST['branch']['name'] ?? '' ?>">
                                </div>
                            </div>
                            <p><strong>Login für Systemadministrator</strong></p>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="login_user">Administrator<span
                                                class="text-danger">*</span></label>
                                    <div class="input-group">
                                    <span class="input-group-text input-group-text-fixed">
                                        <i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" id="login_user" required
                                               name="login[user]"
                                               value="<?php echo $_POST['login']['user'] ?? 'sa' ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="login_pass">Passwort<span
                                                    class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="login_pass" required
                                               name="login[pass]"
                                               value="<?php echo $_POST['login']['pass'] ?? '' ?>">
                                    </div>
                                </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkButton = document.getElementById('mssql_check_btn');
        const checkField = document.getElementById('mssql_check');
        const useField = document.getElementById('mssql_use');

        if (useField && checkField) {
            useField.addEventListener('change', function () {
                if (this.checked && checkField.value !== 'success') {
                    this.checked = false;
                    this.value = '0';

                    showToast(
                        'Bitte prüfe zuerst erfolgreich die Datenbankverbindung.',
                        'warning',
                        'Verbindung erforderlich'
                    );
                    return;
                }

                this.value = this.checked ? '1' : '0';
            });
        }

        if (!checkButton || !checkField) {
            return;
        }

        checkButton.addEventListener('click', async function () {
            const originalText = checkButton.innerHTML;

            const selectedType = document.querySelector('input[name="mssql[typ]"]:checked');
            const formData = new FormData();

            formData.append('mssql[typ]', selectedType ? selectedType.value : 'MSSQL');
            formData.append('mssql[server]', document.getElementById('mssql_server')?.value || '');
            formData.append('mssql[port]', document.getElementById('mssql_port')?.value || '');
            formData.append('mssql[user]', document.getElementById('mssql_user')?.value || '');
            formData.append('mssql[pass]', document.getElementById('mssql_pass')?.value || '');
            formData.append('mssql[db]', document.getElementById('mssql_db')?.value || '');

            checkButton.disabled = true;
            checkButton.innerHTML = 'Prüfe...';

            try {
                const response = await fetch('setup.php?action=check_db', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    checkField.value = 'success';
                    showToast('Datenbankverbindung erfolgreich hergestellt.', 'success', 'Verbindung geprüft');
                } else {
                    checkField.value = 'error';
                    showToast(data.message || 'Verbindung fehlgeschlagen.', 'error', 'Verbindung fehlgeschlagen');
                }
            } catch (error) {
                checkField.value = 'error';
                showToast('Die Verbindungsprüfung konnte nicht ausgeführt werden.', 'error', 'Fehler');
            } finally {
                checkButton.disabled = false;
                checkButton.innerHTML = originalText;
            }
        });
    });
</script>
</body>
</html>
