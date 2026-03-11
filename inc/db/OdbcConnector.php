<?php


class OdbcConnector implements DbConnector
{
    protected $connection;
    protected $log;
    protected $odbcName;
    protected $database;


    /**
     * MssqlConnector constructor.
     *
     * @param string $server
     * @param string $user
     * @param string $password
     * @param int    $cursorOption
     *
     * @throws DatabaseException
     * @throws JsonException
     */
    public function __construct(string $server, string $database, string $user, string $password, int $cursorOption = SQL_CUR_USE_DRIVER, $existingConnection = null)
    {
        $this->log      = new Log('DB');
        $this->odbcName = $server;
        $this->database = $database;
        if (is_resource($existingConnection)) {
            $connection = $existingConnection;
        } else {
            $connection = odbc_connect($server, $user, $password, $cursorOption);
        }
        if ($connection === false) {
            $this->log->log("Fehler bei DB Verbindung: " . odbc_errormsg(), Log::LEVEL_ERROR);
            throw new DatabaseException("Fehler bei DB Verbindung", 0, null, odbc_errormsg());
        }
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function fetchTableColumns(string $tableName)
    {
        $stmt   = odbc_columns($this->connection, null, null, $tableName, $columnName);
        $result = [];
        while ($row = odbc_fetch_array($stmt)) {
            $result[] = $row['COLUMN_NAME'];
        }
        return $result;
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array
     * @throws DatabaseException
     */
    protected function fetchData(string $sql, $params = [])
    {
        $result = $this->executeSql($sql, $params);
        if ($result === false) {
            $this->log->log("Fehler beim 'execute_statement'", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Fehler beim 'execute_statement'", 0, null, odbc_errormsg(), $sql, $params);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeSql(string $sql, $params = [])
    {
        $debug = defined('DB_DEBUG') && DB_DEBUG == 1;
        if ($debug) {
            $starTime = microtime(true);
        }
        $stmt = odbc_prepare($this->connection, $sql);
        if (!$stmt) {
            $this->log->log("Fehler beim 'prepare_statement'", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Fehler beim 'prepare_statement'", 0, null, odbc_errormsg(), $sql, $params);
        }
        if (odbc_execute($stmt, $params) === true && empty(odbc_errormsg())) {
            $affected = odbc_num_rows($stmt);
            if ($debug) {
                $duration = (microtime(true) - $starTime) * 1000;
                $dbLog    = new Log('Queries', 1);
                $dbLog->log($sql, Log::LEVEL_DEBUG, [['time' => $duration . 'ms', 'params' => $params]]);
            }
            $result = [];
            while ($row = odbc_fetch_array($stmt)) {
                $result[] = $row;
            }

            if (empty($result) && $affected > 0) {
                return $affected;
            }

            return $result;
        }
        $this->log->log("Fehler beim ausführen der DB Abfrage", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
        throw new DatabaseException("Fehler beim ausführen der DB Abfrage, siehe DB Log.", 0, null, odbc_errormsg(), $sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function getDistinctValues(string $table, string $field)
    {
        $table = str_replace([']', '['], '', $table);
        $sql   = "SELECT DISTINCT ? FROM [$table]";
        return $this->fetchData($sql, [$field]);
    }

    /**
     * @inheritDoc
     */
    public function count(string $table, $where = null)
    {
        $sql    = "SELECT COUNT(*) as count FROM [$table]";
        $params = [];
        if (!empty($where)) {
            $whereData = $this->buildWhere($where);
            $sql       .= ' ' . $whereData['string'];
            $params    = $whereData['params'];
        }
        $result = $this->fetchData($sql, $params);
        return $result[0]['count'];
    }

    /**
     * @param $where
     *
     *  $where = ['id' => 1] ------------------------------------------- WHERE id = 1
     *  $where = ['id' => 2, 'name' => 'Max'] -------------------------- WHERE id = 2 AND name = "Max"
     *  $where = ['LIKE' => ['name' => 'Max'], 'city' => 'Paderborn'] -- WHERE name LIKE "%Max%" AND city = "Paderborn"
     *  $where = "name like 'Max%' or name like 'Moe%'" ---------------- WHERE name LIKE 'Max%' OR name LIKE 'Moe%'
     *
     * @return array|false|string contains 'string' and 'params' as an array, which can be passed to a prepare statement
     */
    protected function buildWhere($where)
    {

        if (is_string($where)) {
            if (strpos($where, 'WHERE') === false) {
                return 'WHERE ' . $where;
            }
            return $where;
        }

        if (is_array($where)) {
            $like = null;
            if (array_key_exists('LIKE', $where)) {
                $like = $where['LIKE'];
                unset($where['LIKE']);
            }
            $nullParams = [];
            $inParams   = [];
            foreach ($where as $field => $value) {
                if ($value === null) {
                    $nullParams[] = $field;
                    unset($where[$field]);
                }
                if (is_array($value)) {
                    $inParams[$field] = $value;
                    unset($where[$field]);
                }
            }
            $whereString = 'WHERE ';
            if (!empty($where)) {
                $whereString .= implode(" = ? AND ", array_keys($where)) . ' = ?';
            }
            if (!empty($like)) {
                foreach ($like as $field => &$value) {
                    $value = '%' . $value . '%';
                }
                unset($value);
                if ($whereString !== 'WHERE ') {
                    $whereString .= ' AND';
                }
                $whereString .= implode(" LIKE '%?%' AND ", array_keys($like)) . " LIKE ?";
                $where       = array_merge($where, $like);
            }
            if (!empty($inParams)) {
                foreach ($inParams as $field => $inParam) {
                    if ($whereString !== 'WHERE ') {
                        $whereString .= ' AND';
                    }
                    $whereString .= " [$field] IN ('" . implode("', '", $inParam) . "')";
                }
            }
            if (!empty($nullParams)) {
                if ($whereString !== 'WHERE ') {
                    $whereString .= ' AND ';
                }
                $whereString .= implode(" IS NULL AND ", $nullParams) . ' IS NULL';
            }
            return ['string' => $whereString, 'params' => array_values($where)];
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getTableNames($withViews = false)
    {
        if (!$withViews) {
            $viewParam = 'TABLE';
        } else {
            $viewParam = null;
        }
        $stmt   = odbc_tables($this->connection, null, null, null, $viewParam);
        $result = [];
        while ($row = odbc_fetch_array($stmt)) {
            $result[$row['TABLE_NAME']] = $row['TABLE_NAME'];
        }
        return array_values($result);
    }

    /**
     * @inheritDoc
     */
    public function tableExists(string $tableName)
    {
        $stmt   = odbc_tables($this->connection, null, null, $tableName);
        $result = [];
        while ($row = odbc_fetch_array($stmt)) {
            $result[$row['TABLE_NAME']] = $row['TABLE_NAME'];
        }
        return !empty($result);
    }

    /**
     * @inheritDoc
     */
    public function rowExists(string $table, $where)
    {
        $result = $this->getOne($table, $where);
        return !empty($result);
    }

    /**
     * @inheritDoc
     */
    public function getOne(string $table, $where = null, $select = '*')
    {
        $result = $this->getAll($table, $select, $where, null, 1);
        if (!empty($result)) {
            return $result[0];
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAll(string $table, $select = '*', $where = null, $orderBy = null, $limit = null, $offset = null, $groupBy = null, $whereParams = [], $joinString = '')
    {
        $sql = "SELECT ";
        if ($limit !== null && $offset === null) {
            $sql .= "TOP $limit ";
        }
        $sql         .= " $select FROM $table $joinString";
        $whereResult = $this->buildWhere($where);
        $params      = [];
        if (!empty($whereResult)) {
            if (is_string($whereResult)) {
                $sql .= ' ' . $whereResult;
            } else {
                $sql    .= ' ' . $whereResult['string'];
                $params = $whereResult['params'];
            }
        }
        if (!empty($groupBy)) {
            if (strpos($groupBy, 'GROUP BY') === false) {
                $groupBy = 'GROUP BY ' . $groupBy;
            }
            $sql .= " $groupBy";
        }
        if (!empty($orderBy)) {
            if (strpos($orderBy, 'ORDER BY') === false) {
                $orderBy = 'ORDER BY ' . $orderBy;
            }
            $sql .= " " . $orderBy;
        }
        if ($offset !== null) {
            $sql .= " OFFSET $offset ROWS";
            if ($limit !== null) {
                $sql .= " FETCH FIRST $limit ROWS ONLY";
            }
        }
        return $this->fetchData($sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function columnExists(string $tableName, string $columnName)
    {
        $stmt   = odbc_columns($this->connection, null, null, $tableName, $columnName);
        $result = [];
        while ($row = odbc_fetch_array($stmt)) {
            $result[] = $row;
        }
        return !empty($result);
    }

    /**
     * @inheritDoc
     */
    public function insertRow(string $table, array $data)
    {
        $insertData   = $this->formatSqlData($data);
        $placeHolder  = array_fill(0, count($insertData), '?');
        $fieldString  = implode('] ,[', array_keys($insertData));
        $valuesString = implode(", ", $placeHolder);
        $sql          = "INSERT INTO $table  
                ([$fieldString])
                VALUES
                ($valuesString);
                ";
        $params       = array_values($insertData);
        $execute      = $this->executeSql($sql, $params);
        if ($execute === false) {
            $this->log->log("Insert-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Insert-Befehl fehlgeschlagen", 0, null, odbc_errormsg(), $sql, $params);
        }
        $id = $this->fetchData("SELECT @@IDENTITY AS ID");
        return $id[0]['ID'];
    }

    protected function formatSqlData($data)
    {
        $sqlValues = [];
        foreach ($data as $key => $val) {
            $isDate = preg_match('/^(\d{2}\.\d{2}\.\d{4}|\d{4}-\d{2}-\d{2})( \d{2}:\d{2}:\d{2})?(\.\d{1,3})?$/', $val, $output_array);
            //Konvertiere den Wert erst in ein Integer und dann zurück in einen String, damit eine Kundenummer z.B. nicht als Datum ausgewertet wird
            if ($isDate) {
                $sqlValues[$key] = date('Y-m-d\TH:i:s', strtotime($val));
            } elseif ($val === null) {
                $sqlValues[$key] = null;
            } elseif (!is_int($val)) {
                $sqlValues[$key] = trim($val);
            } else {
                $sqlValues[$key] = $val;
            }
        }
        return $sqlValues;
    }

    public function deleteRow(string $table, $where)
    {
        $sql         = "DELETE $table";
        $whereResult = $this->buildWhere($where);
        $params      = [];
        if (!empty($whereResult)) {
            if (is_string($whereResult)) {
                $sql .= ' ' . $whereResult;
            } else {
                $sql    .= ' ' . $whereResult['string'];
                $params = $whereResult['params'];
            }
        }
        $execute = $this->executeSql($sql, $params);
        if ($execute === false) {
            $this->log->log("Delete-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Delete-Befehl fehlgeschlagen", 0, null, odbc_errormsg(), $sql, $params);
        }
        return $execute;
    }

    /**
     * @inheritDoc
     */
    public function update(string $table, array $data, $where)
    {
        $updateData  = $this->formatSqlData($data);
        $fieldString = implode('] = ? ,[', array_keys($updateData));
        $sql         = "UPDATE $table SET
                [$fieldString] = ?
                ";

        $whereResult = $this->buildWhere($where);
        $params      = array_values($updateData);
        if (!empty($whereResult)) {
            if (is_string($whereResult)) {
                $sql .= ' ' . $whereResult;
            } else {
                $sql    .= ' ' . $whereResult['string'];
                $params = array_merge($params, $whereResult['params']);
            }
        }
        $execute = $this->executeSql($sql, $params);
        if ($execute === false) {
            $this->log->log("Update-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => odbc_errormsg(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Update-Befehl fehlgeschlagen", 0, null, odbc_errormsg(), $sql, $params);
        }
        return $execute;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionType()
    {
        return DbConnector::CONNECTION_ODBC;
    }

    /**
     * @inheritDoc
     */
    public function getServerInfo()
    {
        return new ServerInfo($this->odbcName, DbConnector::CONNECTION_ODBC, '-', $this->database);
    }

    /**
     * @inheritDoc
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    public function getDatabases()
    {
        return [$this->database];
    }

    public function createTable(string $table, array $fields)
    {
        throw new DatabaseException("Creating Databases is not supported via ODBC");
    }

    public function primaryKey()
    {
        return 'int IDENTITY(1,1) PRIMARY KEY';
    }

    public function dateTime()
    {
        return 'datetime';
    }

    public function date()
    {
        return 'date';
    }

    public function decimal(int $length, int $decimals)
    {
        return "decimal($length,$decimals)";
    }

    public function string(int $length)
    {
        return 'varchar(' . $length . ')';
    }

    public function int()
    {
        return 'int';
    }

    public function text()
    {
        return 'text';
    }

    public function float()
    {
        return 'float';
    }

    public function ping()
    {
        try {
            if ($this->connection === null) {
                return false;
            }
            $result = $this->executeSql("SELECT 1");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
}