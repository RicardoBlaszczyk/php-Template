<?php

/**
 * @param $array
 * @param $file
 *
 * @throws Exception
 */
function write_php_ini($array, $file)
{
    // *.ini Verzeichnis
    createDir(dirname(__FILE__, 2) . '/sicher/ini');

    $res         = array();
    $dbConfigRes = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval) {
                // nur Datenbank
                if (in_array(strtoupper($key), array('MSSQL', 'PROJECT', 'VERSION', 'LOGIN'))) {
                    $const_key = strtoupper($key) . '_' . strtoupper($skey);
                    if ($const_key === 'LOGIN_PASS') {
                        if (!empty($sval)) {
                            $pass_hash = password_hash($sval, PASSWORD_DEFAULT);
                        } else {
                            $pass_hash = defined('LOGIN_PASS') ? LOGIN_PASS : false;
                        }
                        $res[] = $const_key . " = '" . $pass_hash . "'";
                    } else {
                        $res[] = $const_key . " =  " . (is_numeric($sval) ? $sval : "'" . $sval . "'");
                    }
                    unset($array[$key][$skey]);
                } else {
                    $const_key               = strtoupper($key) . '_' . strtoupper($skey);
                    $dbConfigRes[$const_key] = $sval;
                }
            }
        } else {
            $dbConfigRes[$const_key] = !is_array($val) ? $val : json_encode($val);
        }
    }
    // zusammenfassen *.ini Werte
    $res = implode("\r\n", $res);
    // process.ini speichern
    safefilerewrite(str_replace('config', 'process', $file), $res);
    // db konfig speichern
    safeDBconfig($dbConfigRes);
    // updates speichern
    check_config_changes();
    // config.ini speichern
    safefilerewrite($file, $res);
}

/**
 * @param $dbConfigRes
 *
 * @return false
 */
function safeDBconfig($dbConfigRes)
{
    if (!empty($dbConfigRes)) {
        foreach ($dbConfigRes as $key => $val) {
            $exists = config::getConfigByKey($key);
            if (empty($exists)) {
                config::createConfig($key, $val);
            } else {
                $exists->value = $val;
                $exists->saveToDb();
            }
        }
    }
    return false;
}

/**
 * @return void
 * @throws JsonException
 */
function check_config_changes()
{
    $log = new log("konfiguration");
    if (defined('LOGIN_USER') && !empty(LOGIN_USER)) {
        $log->log("Überschreiben durch DiVA-Interface_user " . LOGIN_USER . " angestoßen.", "INFO");
    } else {
        $log->log("Überschreiben angestoßen.", "INFO");
    }
    createDir(dirname(__FILE__, 2) . '/sicher/ini');

    if (is_file(dirname(__FILE__, 2) . '/sicher/ini/process.ini')) {
        $dataToSave = parse_ini_file(dirname(__FILE__, 2) . '/sicher/ini/process.ini');
        if (is_file(dirname(__FILE__, 2) . '/sicher/ini/config.ini')) {
            $configDataOld = parse_ini_file(dirname(__FILE__, 2) . '/sicher/ini/config.ini');
        } else {
            $configDataOld = array();
        }

        if (!empty($dataToSave) && !empty($configDataOld)) {

            $differentConfigValue['VALUES']['old']     = count($configDataOld);
            $differentConfigValue['VALUES']['process'] = count($dataToSave);

            foreach ($dataToSave as $key => $val) {
                // wenn daten in alter config vorhanden sind
                if (isset($configDataOld[$key])) {
                    // wenn neue daten unterschiedlich zu alten daten
                    if ($dataToSave[$key] != $configDataOld[$key]) {
                        $differentConfigValue[$key]['old']     = isJson($configDataOld[$key]) ? json_decode($configDataOld[$key]) : $configDataOld[$key];
                        $differentConfigValue[$key]['process'] = isJson($dataToSave[$key]) ? json_decode($dataToSave[$key]) : $dataToSave[$key];
                    } else {
                        // nothing to do
                    }
                } else {
                    // wenn alte daten nicht mehr zu speichern sind
                    if(isset($configDataOld[$key])) {
                        $differentConfigValue[$key]['old']     = isJson($configDataOld[$key]) ? json_decode($configDataOld[$key]) : $configDataOld[$key];
                        $differentConfigValue[$key]['process'] = 'Aus *.ini entfernt';
                    }
                }
            }
        }

        if (!empty($differentConfigValue)) {
            $differentConfigValues['log'] = $differentConfigValue;
            $log->log("Update *.ini-Daten ", "INFO", $differentConfigValues);
        }
        @unlink(dirname(__FILE__, 2) . '/sicher/ini/process.ini');
    }
}

/**
 * @param $fileName
 * @param $dataToSave
 *
 * @throws Exception
 */
function safefilerewrite($fileName, $dataToSave)
{
    if (basename($fileName) !== 'config.ini' && basename($fileName) !== 'process.ini' && substr(basename($fileName), -3) === 'ini') {
        $dataToSave = base64_encode($dataToSave);
    }
    if ($fp = fopen($fileName, 'wb')) {
        $startTime = microtime(true);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) {
                usleep(round(random_int(0, 100) * 1000));
            }
        } while ((!$canWrite) and ((microtime(true) - $startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}

function checkDb($databaseInfo)
{
    $errors = [];
    try {
        $sqlErrors  = null;
        $connection = false;
        switch ($databaseInfo['typ']) {
            case DbConnector::CONNECTION_MSSQL:
                $connection = new MssqlConnector(
                    $databaseInfo['server'],
                    $databaseInfo['db'],
                    $databaseInfo['user'],
                    $databaseInfo['pass'],
                    $databaseInfo['port'] ?? null
                );
                $sqlErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
                if (!empty($sqlErrors)) {
                    $errors[] = "<pre>" . print_r($sqlErrors, true) . "</pre>";
                }
                break;

            case DbConnector::CONNECTION_MYSQL:
                $connection = new MysqlConnector(
                    $databaseInfo['server'],
                    $databaseInfo['db'],
                    $databaseInfo['user'],
                    $databaseInfo['pass'],
                    $databaseInfo['port'] ?? null
                );
                $sqlErrors = mysqli_connect_error();
                if (!empty($sqlErrors)) {
                    $errors[] = "<pre>" . $sqlErrors . "</pre>";
                }
                break;

            case DbConnector::CONNECTION_ODBC:
                if (!class_exists('OdbcConnector')) {
                    include_once dirname(__FILE__) . '/db/OdbcConnector.php';
                }

                $connection = new OdbcConnector(
                    $databaseInfo['server'],
                    $databaseInfo['db'],
                    $databaseInfo['user'],
                    $databaseInfo['pass']
                );
                $sqlErrors = odbc_errormsg();
                if (!empty($sqlErrors)) {
                    $errors[] = "<pre>" . $sqlErrors . "</pre>";
                }
                break;

            default:
                $errors[] = "Kein gültiger DB Typ im Setup angegeben: " . ($databaseInfo['typ'] ?? '');
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
function checkDbServerConnection(array $databaseInfo)
{
    $type = strtoupper((string)($databaseInfo['typ'] ?? 'MSSQL'));

    switch ($type) {
        case 'MSSQL':
            $server   = trim((string)($databaseInfo['server'] ?? ''));
            $port     = trim((string)($databaseInfo['port'] ?? ''));
            $user     = (string)($databaseInfo['user'] ?? '');
            $password = (string)($databaseInfo['pass'] ?? '');

            $serverName = $server;
            if ($port !== '') {
                $serverName .= ',' . $port;
            }

            $connectionOptions = [
                'Database' => 'master',
                'UID' => $user,
                'PWD' => $password,
                'CharacterSet' => 'utf-8',
                'ReturnDatesAsStrings' => true,
            ];

            $connection = sqlsrv_connect($serverName, $connectionOptions);
            if ($connection === false) {
                return sqlsrv_errors();
            }

            sqlsrv_close($connection);
            return true;

        case 'MYSQL':
            $server   = trim((string)($databaseInfo['server'] ?? ''));
            $port     = trim((string)($databaseInfo['port'] ?? ''));
            $user     = (string)($databaseInfo['user'] ?? '');
            $password = (string)($databaseInfo['pass'] ?? '');

            $port = $port !== '' ? (int)$port : 3306;

            $connection = mysqli_init();
            if ($connection === false) {
                return ['MySQL-Verbindung konnte nicht initialisiert werden.'];
            }

            $connected = mysqli_real_connect($connection, $server, $user, $password, null, $port);
            if ($connected === false) {
                return [mysqli_connect_error()];
            }

            mysqli_close($connection);
            return true;

        case 'ODBC':
            return ['Server-Prüfung für ODBC wird aktuell nicht unterstützt.'];

        default:
            return ['Unbekannter Datenbanktyp.'];
    }
}

function hasDatabaseName(array $databaseInfo): bool
{
    return trim((string)($databaseInfo['db'] ?? '')) !== '';
}

function validateDatabaseName(string $databaseName): void
{
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $databaseName)) {
        throw new RuntimeException('Der Datenbankname enthält ungültige Zeichen.');
    }
}

function createMssqlDatabaseIfNotExists(array $databaseInfo): void
{
    $server   = trim((string)($databaseInfo['server'] ?? ''));
    $port     = trim((string)($databaseInfo['port'] ?? ''));
    $user     = (string)($databaseInfo['user'] ?? '');
    $password = (string)($databaseInfo['pass'] ?? '');
    $database = trim((string)($databaseInfo['db'] ?? ''));

    if ($database === '') {
        return;
    }

    validateDatabaseName($database);

    $serverName = $server;
    if ($port !== '') {
        $serverName .= ',' . $port;
    }

    $connectionOptions = [
        'Database' => 'master',
        'UID' => $user,
        'PWD' => $password,
        'CharacterSet' => 'utf-8',
        'ReturnDatesAsStrings' => true,
    ];

    $connection = sqlsrv_connect($serverName, $connectionOptions);
    if ($connection === false) {
        throw new RuntimeException('Verbindung zum MSSQL-Server fehlgeschlagen.');
    }

    $sql = "
        IF DB_ID(N'{$database}') IS NULL
        BEGIN
            EXEC('CREATE DATABASE [{$database}]')
        END
    ";

    $stmt = sqlsrv_query($connection, $sql);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        sqlsrv_close($connection);
        throw new RuntimeException('MSSQL-Datenbank konnte nicht erstellt werden: ' . print_r($errors, true));
    }

    sqlsrv_close($connection);
}

function createMysqlDatabaseIfNotExists(array $databaseInfo): void
{
    $server   = trim((string)($databaseInfo['server'] ?? ''));
    $port     = trim((string)($databaseInfo['port'] ?? ''));
    $user     = (string)($databaseInfo['user'] ?? '');
    $password = (string)($databaseInfo['pass'] ?? '');
    $database = trim((string)($databaseInfo['db'] ?? ''));

    if ($database === '') {
        return;
    }

    validateDatabaseName($database);

    $port = $port !== '' ? (int)$port : 3306;

    $connection = mysqli_init();
    if ($connection === false) {
        throw new RuntimeException('MySQL-Verbindung konnte nicht initialisiert werden.');
    }

    $connected = mysqli_real_connect($connection, $server, $user, $password, null, $port);
    if ($connected === false) {
        throw new RuntimeException('Verbindung zum MySQL-Server fehlgeschlagen: ' . mysqli_connect_error());
    }

    $sql = "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!mysqli_query($connection, $sql)) {
        $error = mysqli_error($connection);
        mysqli_close($connection);
        throw new RuntimeException('MySQL-Datenbank konnte nicht erstellt werden: ' . $error);
    }

    mysqli_close($connection);
}

function createDatabaseIfNotExists(array $databaseInfo): void
{
    if (!hasDatabaseName($databaseInfo)) {
        return;
    }

    $type = strtoupper((string)($databaseInfo['typ'] ?? 'MSSQL'));

    switch ($type) {
        case 'MSSQL':
            createMssqlDatabaseIfNotExists($databaseInfo);
            break;

        case 'MYSQL':
            createMysqlDatabaseIfNotExists($databaseInfo);
            break;

        case 'ODBC':
            throw new RuntimeException('Automatisches Anlegen einer Datenbank wird für ODBC aktuell nicht unterstützt.');

        default:
            throw new RuntimeException('Unbekannter Datenbanktyp: ' . $type);
    }
}

/**
 * @param $filePath
 *
 * @return array|false
 */
function loadIniFile($filePath)
{
    $fileContent = file_get_contents($filePath);
    $baseDecoded = base64_decode($fileContent);
    $iniLoaded   = @parse_ini_string($baseDecoded);
    if ($iniLoaded === false || empty($iniLoaded)) {
        $iniLoaded = @parse_ini_string($fileContent);
    }

    return $iniLoaded;
}

/**
 * @param     $bytes
 * @param int $decimals
 *
 * @return string
 */
function human_filesize($bytes, $decimals = 2)
{
    $sz     = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / (1024 ** $factor)) . @$sz[$factor];
}

/**
 * @param $name
 *
 * @return array|string|string[]|null
 */
function set_filename($name)
{
    $return = preg_replace('/[^a-zA-Z0-9üäöÜÄÖß-]/', '-', $name);
    return str_replace(['--', '---', '----'], '-', $return);
}


/**
 * @param $path
 *
 * @return void
 */
function removeDir($path)
{
    $dir = opendir($path);
    while (false !== ($file = readdir($dir))) {
        if (($file !== '.') && ($file !== '..')) {
            $full = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full)) {
                removeDir($full);
            } else {
                unlink($full);
            }
        }
    }
    closedir($dir);

    // Sicherstellen, dass Handles zu sind
    clearstatcache();
    // Kurze Pause für Windows Filesystem
    usleep(100000);

    rmdir($path);
}


/**
 * Erstellt Verzeichnis rekursiv mit korrekten Berechtigungen
 *
 * @param string $path      Verzeichnispfad
 * @param int    $mode      Berechtigungen (Oktalzahl)
 * @param bool   $recursive Rekursiv erstellen
 *
 * @return bool True bei Erfolg oder wenn bereits existiert
 * @throws RuntimeException Bei Fehlern
 */
function createDir($path, $mode = 0777, $recursive = true)
{
    // Bereits vorhanden?
    if (is_dir($path)) {
        return true;
    }

    // Erstellen
    if (!mkdir($path, $mode, $recursive)) {
        $error = error_get_last();
        throw new RuntimeException(
            "Konnte Verzeichnis nicht erstellen: $path. " .
            ($error ? $error['message'] : 'Unbekannter Fehler')
        );
    }

    // Berechtigungen explizit setzen (wegen umask)
    chmod($path, $mode);

    return true;
}

/**
 * @param $path
 *
 * @return void
 */
function clearDir($path)
{

    $files = glob($path . '/*'); // Ersetzen Sie durch den Pfad zu Ihrem Verzeichnis

    foreach ($files as $file) {
        if (is_file($file))
            unlink($file); // Löscht die Datei
    }
}

/**
 * Transform $_FILES
 *
 * array(1) {
 *  ["upload"]=>array(2) {
 *      ["name"]=>array(2) {
 *          [0]=>string(9)"file0.txt"
 *          [1]=>string(9)"file1.txt"
 *      }
 *      ["type"]=>array(2) {
 *          [0]=>string(10)"text/plain"
 *          [1]=>string(10)"text/html"
 *      }
 *  }
 * }
 *
 * into...
 *
 * array(1) {
 *  ["upload"]=>array(2) {
 *      [0]=>array(2) {
 *          ["name"]=>string(9)"file0.txt"
 *          ["type"]=>string(10)"text/plain"
 *      },
 *      [1]=>array(2) {
 *          ["name"]=>string(9)"file1.txt"
 *          ["type"]=>string(10)"text/html"
 *      }
 *  }
 * }
 *
 * @param $vector
 *
 * @return array
 */
function diverse_array($files)
{
    $result = array();
    foreach ($files as $key1 => $value1)
        foreach ($value1 as $key2 => $value2)
            $result[$key2][$key1] = $value2;
    return $result;
}

/**
 * @param     $verzeichnis
 * @param int $last_x_days
 *
 * @return array
 */
function dir_rekursiv($verzeichnis, $last_x_days = 5)
{
    //echo date('d.m.Y H:i:s', strtotime('-' . $last_x_days . ' days') ). '<br/>';
    $arr_last_files = array();
    if (is_dir($verzeichnis)) {
        $verzeichnis .= (substr($verzeichnis, -1) == '/' ? '' : '/');
        $handle      = opendir($verzeichnis);
        while ($datei = readdir($handle)) {
            if (($datei != '.') && ($datei != '..')) {
                $file = $verzeichnis . $datei;
                if (is_dir($file)) // Wenn Verzeichniseintrag ein Verzeichnis ist
                {
                    // Erneuter Funktionsaufruf, um das aktuelle Verzeichnis auszulesen
                    $arr_files_tmp = dir_rekursiv($file . '/', $last_x_days);
                    if (!empty($arr_files_tmp)) {
                        array_push($arr_last_files, $arr_files_tmp);
                    }
                    //return $arr_last_files;
                } else {
                    // Wenn Verzeichnis-Eintrag eine Datei ist, diese ausgeben
                    // TODO: Hier etwas mit der Datei tun
                    if (filemtime($file) >= strtotime('-' . $last_x_days . ' days')) {
                        $arr_last_files[] = $file;
                    }
                }
            }
        }
        closedir($handle);
    }
    return $arr_last_files;
}

/**
 * @param $arr
 * @param $col
 * @param $dir
 *
 * @return mixed
 */
function array_sort_by_column(&$arr, $col, $dir = SORT_ASC)
{
    $sort_col = array();
    foreach ($arr as $key => $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
    return $arr;
}

/**
 * @return string
 */
function getProtocol()
{

    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }

    return $protocol;
}

/**
 * @return string
 */
function getServerBaseUrl()
{

    $protocol = getProtocol();
    // Hostname
    $host = $_SERVER['HTTP_HOST'];

    return $protocol . $host;
}

/**
 * Compares two values from arrays in ascending order based on the global variable $orderColumn.
 *
 * @param array $a The first array to compare.
 * @param array $b The second array to compare.
 *
 * @return int Returns a negative value if $a is less than $b, 0 if they are equal,
 *             or a positive value if $a is greater than $b based on the comparison of $orderColumn.
 */
function compareResultASC($a, $b)
{
    global $orderColumn;
    if (is_numeric($a) && is_numeric($b)) {
        return $a <=> $b;
    }
    return strcmp($a[$orderColumn], $b[$orderColumn]);
}

/**
 * Compares the result of two arrays in descending order based on the value of the column specified by the global
 * variable $orderColumn.
 *
 * @param array $a The first array to compare.
 * @param array $b The second array to compare.
 *
 * @return int Returns a negative, zero, or positive value based on whether the value of column specified by
 *             $orderColumn in array $b is less than, equal to, or greater than the value in array $a, respectively.
 */
function compareResultDESC($a, $b)
{
    global $orderColumn;
    return strcmp($b[$orderColumn], $a[$orderColumn]);
}

function getExtensionFromMimeType($mimeType)
{
    static $mimeTypes = [
        "image/jpeg"      => "jpeg",
        "image/png"       => "png",
        "image/gif"       => "gif",
        "application/pdf" => "pdf",
        // add more mappings as required
    ];

    return $mimeTypes[$mimeType] ?? null;
}

/**
 * @param $needle
 * @param $haystack
 *
 * @return bool
 */
function recursiveArraySearch($needle, $haystack)
{
    foreach ($haystack as $key => $value) {
        if (is_array($value) && recursiveArraySearch($needle, $value) !== false) {
            return true;
        }
        if ($value === $needle) {
            return true;
        }
    }
    return false;
}

/**
 * @param $string
 *
 * @return bool
 */
function isJson($string)
{
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}

/**
 * @param $string
 *
 * @return mixed|void
 */
function jsonValidate($string)
{
    // decode the JSON data
    $result = json_decode($string);

    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid // No error has occurred
            break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
    }

    if ($error !== '') {
        // throw the Exception or exit // or whatever :)
        exit($error);
    }

    // everything is OK
    return $result;
}

/**
 * @param $string
 *
 * @return array|string|string[]|null
 */
function create_slug($string)
{
    $table = array(
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    );

    // replace special characters
    $string = str_replace(array_keys($table), array_values($table), $string);

    // lowercase
    $string = strtolower($string);

    // replace spaces with dashes
    $string = str_replace(' ', '-', $string);

    // remove everything but alphanumeric characters and dashes
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

    // replace multiple consecutive dashes with a single dash
    $string = preg_replace('/-+/', '-', $string);

    return $string;
}

/**
 * @param $sourceDir
 * @param $targetDir
 *
 * @return bool
 */
function moveDirectoryContents($sourceDir, $targetDir)
{
    // Stelle sicher, dass Pfade mit / enden
    $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Prüfen, ob Quellverzeichnis existiert
    if (!is_dir($sourceDir)) {
        echo "Quellverzeichnis nicht gefunden: $sourceDir\n";
        return false;
    }

    // Zielverzeichnis erstellen, wenn es nicht existiert
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $items = scandir($sourceDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $sourceDir . $item;
        $targetPath = $targetDir . $item;

        if (is_dir($sourcePath)) {
            // Rekursiver Aufruf für Unterordner
            moveDirectoryContents($sourcePath, $targetPath);
            // Quellordner löschen, wenn leer
            rmdir($sourcePath);
        } elseif (is_file($sourcePath)) {
            // Datei verschieben
            if (rename($sourcePath, $targetPath)) {
                echo "Datei verschoben: $sourcePath → $targetPath\n";
            } else {
                echo "Fehler beim Verschieben: $sourcePath\n";
            }
        }
    }
    return true;
}

// IP ermitteln
function getClientIp()
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// IP im Range prüfen
function ip_in_range($ip, $net, $mask)
{
    $ip_dec   = ip2long($ip);
    $net_dec  = ip2long($net);
    $mask_dec = -1 << (32 - $mask);
    $net_dec  &= $mask_dec; // Netzanteil
    return ($ip_dec & $mask_dec) === $net_dec;
}


function moveFilesRecursively($source, $destination)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0777, true);
            }
        } else {
            rename($item, $destPath);
        }
    }
}

function areConstantsEmpty(array $consts): bool
{
    foreach ($consts as $const) {
        if (!defined($const) || !empty(constant($const))) {
            return false; // nicht leer
        }
    }
    return true; // alle leer
}

function array_key_exists_recursive(string $key, array $array): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }
    foreach ($array as $value) {
        if (is_array($value) && array_key_exists_recursive($key, $value)) {
            return true;
        }
    }
    return false;
}


/**
 * Normalisiert einen Namen für URLs.
 * - Transliteration (Umlaute/Akzente -> ASCII)
 * - Entfernt ungültige Zeichen
 * - Modus "hyphen": Leer-/Trennzeichen -> "-", mehrfaches "-" verhindern, alles lowercase
 * - Modus "camel":  camelCase (erstes Wort klein, folgende groß), nur [a-z0-9]
 * - Präfix "x", falls der Name mit Ziffer beginnt
 *
 * @param string $value Eingabestring
 * @param string $mode  'hyphen' (Standard) oder 'camel'
 *
 * @return string URL-konformer Name
 */
function normalize_url_name($value, string $mode = 'hyphen'): string
{
    // 0) Umlaute und ß manuell mappen (falls iconv nicht sauber arbeitet)
    $map   = [
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        'ß' => 'ss',
    ];
    $value = strtr($value, $map);

    // 1) Transliteration (Rest-Akzente entfernen, z.B. é → e)
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($normalized === false) {
        $normalized = $value;
    }

    // 2) Alles, was kein Buchstabe/Ziffer ist, als Trenner behandeln
    $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $normalized) ?? '';
    $normalized = trim($normalized);

    if ($normalized === '') {
        return 'x';
    }

    if ($mode === 'camel') {
        // camelCase
        $parts = preg_split('/\s+/', $normalized) ?: [];
        $camel = '';
        foreach ($parts as $i => $part) {
            $lower = strtolower($part);
            $camel .= $i === 0 ? $lower : ucfirst($lower);
        }
        $out = preg_replace('/[^a-z0-9]/', '', strtolower($camel)) ?? '';
    } else {
        // hyphen/kebab-case
        $hyphen = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $hyphen = preg_replace('/-+/', '-', $hyphen) ?? $hyphen;
        // $out    = trim(strtolower($hyphen), '-'); // alles Klein und mit - als Trenner
        $out = trim($hyphen, ' ');
    }

    // 4) Falls leer oder mit Ziffer beginnend -> Präfix
    if ($out === '' || ctype_digit($out[0])) {
        $out = 'x' . $out;
    }

    return $out;

    /**
     * echo normalize_url_name('Kunde Müller GmbH & Co. KG');
     * // "kunde-mueller-gmbh-co-kg"
     *
     * echo normalize_url_name('Kunde Müller GmbH & Co. KG', 'camel');
     * // "kundeMuellerGmbhCoKg"
     *
     * echo normalize_url_name('Äüö – Spezial 123!', 'camel');
     * // "aeoeSpezial123"
     *
     * echo normalize_url_name('Straße Berlin');
     * // "strasse-berlin"
     */

    /**
     * Wandelt ein Array in INI-Format um (mit Sections/Keys).
     *
     * - Top-Level-Keys mit Array-Wert => [section]
     * - Scalars => key=value
     * - List-Arrays => key[]=value (mehrfach)
     */
}

function first_part_before_underscore_lower(string $text): string
{
    $pos = strpos($text, '_');
    $first = ($pos === false) ? $text : substr($text, 0, $pos);

    return mb_strtolower($first, 'UTF-8');
}
