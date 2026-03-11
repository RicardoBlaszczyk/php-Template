<?php


class Log
{
    public const LEVEL_INFO        = 'INFO';
    public const LEVEL_DEBUG       = 'DEBUG';
    public const LEVEL_WARNING     = 'WARNING';
    public const LEVEL_ERROR       = 'ERROR';
    public const MAX_FILE_SIZE     = 5242880; //5 MB
    public const MAX_FILES_PER_DAY = 5;
    public const LOGLEVEL_RANKING  = [
        self::LEVEL_ERROR   => 0,
        self::LEVEL_WARNING => 1,
        self::LEVEL_INFO    => 2,
        self::LEVEL_DEBUG   => 3
    ];

    protected $logName;
    protected $logRotation;
    protected $logDir;
    protected $ip;
    protected $site;
    protected $browser;
    protected $logTime;
    protected $logFile;
    protected $logLevel;

    /**
     * Log constructor.
     *
     * @param null|string $logName
     * @param int         $logRotate
     * @param string      $logDir
     *
     * @throws Exception
     */
    public function __construct($logName = null, $logRotate = 14, $logDir = ROOT . 'logs')
    {
        $this->logLevel    = defined('LOG_LVL') ? LOG_LVL : self::LEVEL_DEBUG;
        $this->logName     = $logName;
        $this->logRotation = $logRotate;
        if (!is_dir($logDir) && !mkdir($concurrentDirectory = $logDir, 4777) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $this->logDir = $logDir;
        $this->checkLogRotation();
        $this->setLogPath();
    }

    /**
     * Prüft ob alte Logs existieren und löscht diese, falls älter als "$logrotation" Tage sind
     *
     * @throws Exception
     */
    protected function checkLogRotation()
    {
        if (is_int($this->logRotation)) {
            $logFileAppend = empty($this->logName) ? '' : '-' . $this->logName;
            $files = glob($this->logDir . DIRECTORY_SEPARATOR . '*' . $logFileAppend . '*.log');
            foreach ($files as $filePath) {
                preg_match('/\d{4}-\d{2}-\d{2}/', basename($filePath), $dateMatch);
                if (!empty($dateMatch)) {
                    $dateTime = new DateTime($dateMatch[0]);
                    $currentDate = new DateTime();
                    $dateDiff = $dateTime->diff($currentDate)->days;
                    if ($dateDiff > $this->logRotation) {
                        unlink($filePath);
                    }
                }
            }
        }
    }

    /**
     * @param string $logValue
     * @param string $level
     * @param null|array   $additionalInfo
     *
     * @throws JsonException
     */
    public function log($logValue, $level = self::LEVEL_ERROR, $additionalInfo = null)
    {
        $logRank = self::LOGLEVEL_RANKING[$level];
        $maxRank = self::LOGLEVEL_RANKING[$this->logLevel];
        if ($logRank > $maxRank) {
            return;
        }
        //Update den Logpfad, damit bei einem Skript, welches über lange Zeit läuft auch immer das aktuelle Datum eingetragen wird.
        $this->setLogPath();

        if (!is_string($logValue)) {
            $this->writeToFile(json_encode($logValue), $level, $additionalInfo);
        } else {
            $this->writeToFile($logValue, $level, $additionalInfo);
        }
    }

    /**
     * Schreibt den LogString in die Datei
     *
     * @param string      $string
     * @param string      $level
     * @param null|string|array $additionalInfo
     *
     * @return false|int
     */
    protected function writeToFile($string, $level, $additionalInfo = null)
    {
        $this->checkFileSize();
        $this->getLogValues();
        if (empty($additionalInfo)) {
            $additionalInfo = [$this->ip, $this->browser];
        }
        if(is_array($additionalInfo)) {
            $stringInfo = [];
            foreach($additionalInfo as $info) {
                if(is_string($info)) {
                    $stringInfo[] = $info;
                } else {
                    $stringInfo[] = json_encode($info);
                }
            }
            $additionalInfo = implode('|', $stringInfo);
        }
        $logLine = implode(' | ', [$this->logTime, $level, $string, $this->site, $additionalInfo]);
        $logLine = str_replace(["\n", "\r"], '<br>', $logLine);
        $logLine = str_replace("<br><br>", '<br>', $logLine);
        if (PHP_SAPI === 'cli') {
            echo $logLine . PHP_EOL;
        }
        return file_put_contents($this->logFile, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    protected function writeToDatabase($string, $level, $additionalInfo = null)
    {
        /**
         * TODO: Insert script for Debuglog REST API
         */
    }

    protected function checkFileSize()
    {
        if (is_file($this->logFile) && filesize($this->logFile) > self::MAX_FILE_SIZE) {
            $logBaseName = rtrim($this->logFile, '.log');
            $this->rotateFile($logBaseName . '-01.log', 1);
            rename($this->logFile, $logBaseName . '-01.log');
        }
    }

    protected function setLogPath()
    {
        $logFileAppend = empty($this->logName) ? '' : '-' . $this->logName;
        $this->logFile = $this->logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . $logFileAppend . '.log';
    }


    protected function rotateFile($fileName, $no)
    {
        if (is_file($fileName)) {
            if ($no >= self::MAX_FILES_PER_DAY) {
                unlink($fileName);
            } else {
                $logBaseName = rtrim($this->logFile, '.log');
                $newNo = $no + 1;
                $newfileName = $logBaseName . '-' . str_pad($newNo, 2, 0, STR_PAD_LEFT) . '.log';
                if (is_file($newfileName)) {
                    $this->rotateFile($newfileName, $newNo);
                }
                rename($fileName, $newfileName);
            }
        }
    }

    /**
     * Setzt die zusätzlichen Werte für das Log
     */
    protected function getLogValues()
    {
        $this->logTime = date("Y-m-d H:i:s");
        $this->ip = $_SERVER["REMOTE_ADDR"] ?? '-';
        $this->site = $_SERVER['REQUEST_URI'] ?? '-';
        $this->browser = $_SERVER["HTTP_USER_AGENT"] ?? '-';
    }

    public function getFilePath()
    {
        return $this->logFile;
    }
}