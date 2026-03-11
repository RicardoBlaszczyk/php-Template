<?php

class config
{
    public const TOKEN_NAME = 'config';
    public const TABLE_NAME = 'KT_CONFIG';
    public static $currentConfig = null;
    /** @var DbConnector */
    protected static $dbConnection;
    protected static $dbChecked = false;
    public           $key;
    public           $value;

    protected $ID;
    protected $ERSTELLTAM;
    protected $ERSTELLTVON;
    protected $UPDATEAM;
    protected $UPDATEVON;
    protected $GELOESCHT;

    /**
     * User constructor.
     *
     * @param $key
     * @param $value
     * @param $ID
     * @param $ERSTELLTAM
     * @param $ERSTELLTVON
     * @param $UPDATEAM
     * @param $UPDATEVON
     * @param $GELOESCHT
     */
    public function __construct($key,
                                $value = null,
                                $ID = null,
                                $ERSTELLTAM = null,
                                $ERSTELLTVON = null,
                                $UPDATEAM = null,
                                $UPDATEVON = null,
                                $GELOESCHT = 0)
    {
        $this->key         = $key;
        $this->value       = $value;
        $this->ID          = $ID;
        $this->ERSTELLTAM  = $ERSTELLTAM;
        $this->ERSTELLTVON = $ERSTELLTVON;
        $this->UPDATEAM    = $UPDATEAM;
        $this->UPDATEVON   = $UPDATEVON;
        $this->GELOESCHT   = $GELOESCHT;
    }


    /**
     * @param string      $key
     * @param string      $password
     * @param null|string $value
     *
     * @return User
     * @throws DatabaseException
     */
    public static function createConfig($key, $value = null)
    {
        self::checkDatabaseTable();
        $exists = self::$dbConnection->rowExists(self::TABLE_NAME, ['key' => $key]);
        if ($exists) {
            throw new RuntimeException("Konfiguration mit dieser Kombination aus Schlüssel existiert bereits.");
        }
        $config = new self($key, $value);
        $config->saveToDb();
        return $config;
    }

    /**
     * @throws DatabaseException
     */
    protected static function checkDatabaseTable()
    {
        if (!self::$dbChecked) {
            if (empty(self::$dbConnection)) {
                global $connection;
                if (empty($connection)) {
                    throw new RuntimeException("No Database connection available");
                }
                self::$dbConnection = $connection;
            }
            if (!defined('CONFIG_DB_CREATED') || !CONFIG_DB_CREATED) {
                $tableExists = self::$dbConnection->tableExists(self::TABLE_NAME);
                if (!$tableExists) {
                    self::createDatabaseTable();
                }
                file_put_contents(ROOT . DIRECTORY_SEPARATOR . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini', "\r\nCONFIG_DB_CREATED = true", FILE_APPEND);
            }
            self::$dbChecked = true;
        }
    }

    protected static function createDatabaseTable()
    {
        $fields = [
            'ID'          => self::$dbConnection->primaryKey(),
            'key'         => self::$dbConnection->string(100) . ' NOT NULL',
            'value'       => self::$dbConnection->string(4000),
            'ERSTELLTAM'  => self::$dbConnection->dateTime(),
            'UPDATEAM'    => self::$dbConnection->dateTime(),
            'ERSTELLTVON' => self::$dbConnection->string(100),
            'UPDATEVON'   => self::$dbConnection->string(100),
            'GELOESCHT'   => self::$dbConnection->int(),
        ];
        self::$dbConnection->createTable(self::TABLE_NAME, $fields);
    }

    public static function getThisConfig()
    {
        if (self::$currentConfig === null) {
            if (!isset($_SESSION[self::TOKEN_NAME])) {
                self::$currentConfig = false;
                return false;
            }
            self::$currentConfig = self::getConfig($_SESSION[self::TOKEN_NAME]);
            //self::$currentUser = true;
        }
        return self::$currentConfig;
    }

    /**
     * @throws DatabaseException
     */
    public function saveToDb()
    {
        $currentConfig = null;
        if (self::getThisConfig() !== false) {
            $currentConfig = self::getThisConfig()->ID;
        }
        if ($this->ID === null) {
            $this->ERSTELLTAM  = date('Y-m-d H:i:s');
            $this->ERSTELLTVON = $currentConfig;
        }
        $this->UPDATEAM  = date('Y-m-d H:i:s');
        $this->UPDATEVON = $currentConfig;
        $data            = get_object_vars($this);
        unset($data['ID']);
        if ($this->ID === null) {
            $result   = self::$dbConnection->insertRow(self::TABLE_NAME, $data);
            $this->ID = $result;
        } else {
            self::$dbConnection->update(self::TABLE_NAME, $data, ['ID' => $this->ID]);
        }
    }

    /**
     * @param int $ID
     *
     * @return User|false
     * @throws DatabaseException
     */
    public static function getConfig($ID)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getOne(self::TABLE_NAME, ['ID' => $ID]);
        if (!empty($dbResult)) {
            return self::dbVarsToConfig($dbResult);
        }

        return false;
    }

    public static function getConfigs($arr_where = null)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getAll(self::TABLE_NAME, '*', $arr_where);
        if (!empty($dbResult)) {
            return $dbResult;
        }
        return false;
    }

    /**
     * @param array $dbResult
     *
     * @return User
     */
    protected static function dbVarsToConfig($dbResult)
    {
        return new self(
            $dbResult['key'],
            $dbResult['value'],
            $dbResult['ID'],
            $dbResult['ERSTELLTAM'],
            $dbResult['ERSTELLTVON'],
            $dbResult['UPDATEAM'],
            $dbResult['UPDATEVON'],
            $dbResult['GELOESCHT']
        );
    }

    public static function setValuesToConfig($arrValues)
    {
        foreach ($arrValues as $key => $val) {
            self::$$key = $val;
        }
    }

    /**
     * @param string $key
     *
     * @return User|false
     * @throws DatabaseException
     */
    public static function getConfigByKey($key)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getOne(self::TABLE_NAME, ['key' => $key]);
        if (!empty($dbResult)) {
            return self::dbVarsToConfig($dbResult);
        }

        return false;
    }

    /**
     * Get the ID of the user.
     *
     * @return mixed The ID of the user.
     */
    public function getId()
    {
        return $this->ID;
    }
}