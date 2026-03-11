<?php


/**
 * Class Helper
 */
class Helper
{
    /**
     * @param $text
     */
    public static function consoleLog($text) {
        echo $text."\r\n";
    }

    /**
     * @param $text
     *
     * @return false|string
     */
    public static function consolePrompt($text) {
        echo $text;
        $handle = fopen ("php://stdin", 'rb');
        $input = fgets($handle);
        fclose($handle);
        return $input;
    }

    /**
     * @param $array
     * @param $file
     *
     * @throws Exception
     */
    public static function write_php_ini($array, $file)
    {
        $res = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $res[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    $res[] = strtoupper($skey) . " =  " . (is_numeric($sval) ? $sval : "'" . $sval . "'");
                }
            } else {
                $res[] = "$key = " . (is_numeric($val) ? $val : "'" . $val . "'");
            }
        }
        self::safefilerewrite($file, implode("\r\n", $res));
    }

    /**
     * @param $fileName
     * @param $dataToSave
     *
     * @throws Exception
     */
    protected static function safefilerewrite($fileName, $dataToSave){

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

    /**
     * @param $dir
     */
    public static function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..") {
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                        self::rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                    else
                        unlink($dir. DIRECTORY_SEPARATOR .$object);
                }
            }
            rmdir($dir);
        } elseif(file_exists($dir)) {
            unlink($dir);
        }
    }
}