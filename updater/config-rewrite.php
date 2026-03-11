<?php
require_once 'Helper.php';
$config = parse_ini_file('updater.ini');

if (!isset($config['backupDirectory'])) {
    Helper::consoleLog("Backup directory not defined in config");
    die(1);
}
if (!isset($config['installDirectory'])) {
    Helper::consoleLog("Install directory not defined in config");
    die(1);
}
$backupDir  = $config['backupDirectory'] . DIRECTORY_SEPARATOR . 'configs';
$installDir = $config['installDirectory'];

if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
    Helper::consoleLog(sprintf('Directory "%s" was not created', $backupDir));
    die(1);
}

if (!is_dir($installDir)) {
    Helper::consoleLog(sprintf('Install directory does not exist "%s"', $installDir));
    die(1);
}

$prompt = Helper::consolePrompt("This will encode all config files in the 'sicher' directory. Are you sure? (y/n)");

if (strtolower(trim($prompt)) !== 'y') {
    Helper::consoleLog('Canceled');
    die();
}

$configDir = $installDir . DIRECTORY_SEPARATOR . 'sicher';

$path = realpath($installDir . DIRECTORY_SEPARATOR . 'sicher');
//Durchlaufe rekursiv alle Dateien im "sicher" Verzeichnis
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filePath) {
    $fileInfo = pathinfo($filePath);
    if ($fileInfo['extension'] === 'ini' && $fileInfo['filename'] !== 'config' && $fileInfo['filename'] !== 'config_example') {
        //Behandle nur .ini Dateien außer die config.ini
        $fileContent = file_get_contents($filePath);
        $baseDecoded = base64_decode($fileContent);
        $iniLoaded   = parse_ini_string($baseDecoded);
        if ($iniLoaded === false) {
            $fileBackupDir = $backupDir . DIRECTORY_SEPARATOR . basename($fileInfo['dirname']);
            $backupFile    = $fileBackupDir . DIRECTORY_SEPARATOR . basename($filePath);
            if (!is_dir($fileBackupDir)) {
                if (!mkdir($fileBackupDir, 0777, true) && !is_dir($fileBackupDir)) {
                    Helper::consoleLog(sprintf('Directory "%s" was not created', $fileBackupDir));
                    die(1);
                }
            }
            Helper::consoleLog($filePath . " wird konvertiert.");
            copy($filePath, $backupFile);
            Helper::consoleLog("Backup kopiert nach: " . $backupFile);
            $baseEncoded = base64_encode($fileContent);
            $writeResult = file_put_contents($filePath, $baseEncoded);
            if ($writeResult === false) {
                Helper::consoleLog("Konnte Datei " . $filePath . " nicht schreiben.");
            }
        } else {
            Helper::consoleLog($filePath . " ist bereits kodiert");
        }
    }
}
