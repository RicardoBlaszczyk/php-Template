<?php


class MssqlConnector implements DbConnector
{
    protected $connection;
    protected $log;

    /**
     * MssqlConnector constructor.
     *
     * @param string      $server
     * @param string      $database
     * @param string      $user
     * @param string      $password
     * @param null|string $port
     * @param string      $characterSet
     * @param bool        $dateAsString
     * @param resource    $existingConnection
     *
     * @throws JsonException|DatabaseException
     */
    public function __construct(
        string $server,
        string $database,
        string $user,
        string $password,
        $port = null,
        $characterSet = "utf-8",
        $dateAsString = true,
        $existingConnection = null,
        $encrypt = false,
        $trustservercertificate = false)
    {
        $this->log = new Log('DB');
        if ($existingConnection !== null) {
            $this->connection = $existingConnection;
            return;
        }
        $serverName = $server;
        if (!empty($port)) {
            $serverName .= "," . $port;
        }
        $connectionOptions = [
            "Database"               => $database,
            "ReturnDatesAsStrings"   => $dateAsString,
            "CharacterSet"           => $characterSet,
            "UID"                    => $user,
            "PWD"                    => $password,
            "Encrypt"                => $encrypt,
            "TrustServerCertificate" => $trustservercertificate
        ];
        $connection        = sqlsrv_connect($serverName, $connectionOptions);
        if ($connection === false) {
            $this->log->log("Fehler bei DB Verbindung", Log::LEVEL_ERROR, sqlsrv_errors());
            throw new DatabaseException("Fehler bei DB Verbindung", 0, null, sqlsrv_errors());
        }
        $this->connection = $connection;
    }

    /**
     * @param string $table
     * @param string $columnName
     * @param string $columnType
     *
     * @return array|int
     * @throws DatabaseException|JsonException
     */
    public function addTableColumn(string $table, string $columnName, string $columnType)
    {
        $sql    = "ALTER TABLE [$table] ADD [$columnName] $columnType";
        $result = $this->executeSql($sql);
        if ($result === false) {
            $this->log->log("Fehler beim anlegen der Spalte $columnName in der $table Tabelle", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql]);
            throw new DatabaseException("Fehler beim anlegen der Spalte $columnName in der $table Tabelle", 0, null, sqlsrv_errors(), $sql);
        }
        return $result;
    }

    public function modifyTableColumn($tableName, $column, $type, $notNull = null, $default = null)
    {
        $column_definition[] = strtoupper($type);
        $column_definition[] = empty($notNull) ? '' : 'NOT NULL';
        $column_definition[] = $default === null ? '' : 'DEFAULT ' . $default;
        $sql                 = "ALTER TABLE {$tableName} ALTER COLUMN {$column} " . implode(' ', $column_definition) . " ;";
        $params              = array();
        $this->fetchData($sql, $params);
    }

    public function changeTableColumn($tableName, $oldColumnName, $newColumnName, $type, $notNull = null, $default = null)
    {
        $column_definition[] = strtoupper($type);
        $column_definition[] = empty($notNull) ? '' : 'NOT NULL';
        $column_definition[] = $default === null ? '' : 'DEFAULT ' . $default;
        $sql                 = "EXEC sp_rename '{$tableName}.{$oldColumnName}', '{$newColumnName}', 'COLUMN' ";
        $params              = array();
        $this->fetchData($sql, $params);
        $sql = "ALTER TABLE {$tableName} ALTER COLUMN {$newColumnName} " . implode(' ', $column_definition) . " ;";
        $this->fetchData($sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function fetchTableColumns(string $tableName)
    {
        $sql    = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WITH (NOLOCK) WHERE TABLE_NAME = ?";
        $result = [];
        $data   = $this->fetchData($sql, [$tableName]);
        foreach ($data as $row) {
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
            $this->log->log("Fehler beim 'execute_statement'", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Fehler beim 'execute_statement'", 0, null, sqlsrv_errors(), $sql, $params);
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
        $stmt = sqlsrv_prepare($this->connection, $sql, $params);
        if (!$stmt) {
            $this->log->log("Fehler beim 'prepare_statement'", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Fehler beim 'prepare_statement'", 0, null, sqlsrv_errors(), $sql, $params);
        }
        if (sqlsrv_execute($stmt) === true && empty(sqlsrv_errors())) {
            $affected = sqlsrv_rows_affected($stmt);
            if ($debug) {
                $duration = (microtime(true) - $starTime) * 1000;

                if (!isset($GLOBALS['sql_timings'])) {
                    $GLOBALS['sql_timings'] = [];
                }
                $GLOBALS['sql_timings'][] = [
                    'sql' => $sql,
                    'time' => $duration,
                    'engine' => 'MSSQL'
                ];

                $dbLog    = new Log('Queries', 1);
                $dbLog->log($sql, Log::LEVEL_DEBUG, [['time' => $duration . 'ms', 'params' => $params]]);
            }
            if ($affected !== false && $affected > 0) {
                return $affected;
            }
            $result = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
            return $result;
        }
        $this->log->log("Fehler beim ausführen der DB Abfrage", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
        throw new DatabaseException("Fehler beim ausführen der DB Abfrage, siehe DB Log.", 0, null, sqlsrv_errors(), $sql, $params);
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
    protected function buildWhere($where, $params = [])
    {
        if (is_string($where)) {
            if (empty($params)) {
                $params = [];
            }
            if (stripos($where, 'WHERE') === false) {
                return ['string' => 'WHERE ' . $where, 'params' => array_values($params)];
            }
            return ['string' => $where, 'params' => array_values($params)];
        }

        if (is_array($where)) {
            $nullParams = [];
            $inParams   = [];
            $like       = null;
            $or         = null;
            $in         = null;

            if (array_key_exists('LIKE', $where)) {
                $like = $where['LIKE'];
                unset($where['LIKE']);
            }
            if (array_key_exists('OR', $where)) {
                $or = $where['OR'];
                unset($where['OR']);
            }
            if (array_key_exists('IN', $where)) {
                $in = $where['IN'];
                unset($where['IN']);
            }

            foreach ($where as $field => $value) {
                if ($value === null) {
                    $nullParams[] = $field;
                    unset($where[$field]);
                } elseif (is_array($value)) {
                    // klassisches IN
                    $inParams[$field] = $value;
                    unset($where[$field]);
                }
            }

            $whereString = 'WHERE ';
            $clauses     = [];

            // normale Felder
            if (!empty($where)) {
                foreach ($where as $field => $value) {
                    $clauses[] = "[$field] = ?";
                    $params[]  = $value;
                }
            }

            // LIKE
            if (!empty($like)) {
                foreach ($like as $field => $value) {
                    $clauses[] = "[$field] LIKE ?";
                    $params[]  = '%' . $value . '%';
                }
            }

            // OR
            if (!empty($or)) {
                $orClauses = [];
                foreach ($or as $field => $value) {
                    $orClauses[] = "[$field] = ?";
                    $params[]    = $value;
                }
                if (!empty($orClauses)) {
                    $clauses[] = '(' . implode(' OR ', $orClauses) . ')';
                }
            }

            // IN (Top-Level)
            if (!empty($in)) {
                foreach ($in as $field => $values) {
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $clauses[]    = "[$field] IN ($placeholders)";
                    $params       = array_merge($params, $values);
                }
            }

            // IN (klassisches Array)
            if (!empty($inParams)) {
                foreach ($inParams as $field => $values) {
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $clauses[]    = "[$field] IN ($placeholders)";
                    $params       = array_merge($params, $values);
                }
            }

            // NULL
            if (!empty($nullParams)) {
                foreach ($nullParams as $field) {
                    $clauses[] = "[$field] IS NULL";
                }
            }

            $whereString .= implode(' AND ', $clauses);
            // $this->log->log('buildWhere: ', 'ERROR', ['SQL' => ['string' => $whereString, 'params' => $params]]);
            return ['string' => $whereString, 'params' => $params];
        }

        return false;
    }


    /**
     * @inheritDoc
     */
    public function getTableNames($withViews = false)
    {
        $search = ['BASE TABLE'];
        if ($withViews) {
            $search[] = 'VIEW';
        }
        $search = "'" . implode("', '", $search) . "'";
        $sql    = "SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WITH (NOLOCK) WHERE TABLE_TYPE IN ($search)";
        $result = [];
        $data   = $this->fetchData($sql);
        foreach ($data as $row) {
            $result[] = $row['TABLE_NAME'];
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function tableExists(string $tableName)
    {
        return $this->rowExists('INFORMATION_SCHEMA.TABLES WITH (NOLOCK)', ['TABLE_NAME' => $tableName]);
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
        $whereResult = $this->buildWhere($where, $whereParams);
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
        return $this->rowExists('INFORMATION_SCHEMA.COLUMNS WITH (NOLOCK)', ['TABLE_NAME' => $tableName, 'COLUMN_NAME' => $columnName]);
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
            $this->log->log("Insert-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Insert-Befehl fehlgeschlagen", 0, null, sqlsrv_errors(), $sql, $params);
        }
        $id = $this->fetchData("SELECT @@IDENTITY AS ID");
        return $id[0]['ID'];
    }

    protected function formatSqlData($data)
    {
        $sqlValues = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $isDate = preg_match('/^(\d{2}\.\d{2}\.\d{4}|\d{4}-\d{2}-\d{2})([T ])(\d{2}:\d{2}:\d{2})?(\.\d{1,3}|\+\d{2}:\d{2})?$/', $val, $output_array);
            //Konvertiere den Wert erst in ein Integer und dann zurück in einen String, damit eine Kundenummer z.B. nicht als Datum ausgewertet wird
            if ($isDate) {
                $sqlValues[$key] = date('Y-m-d\TH:i:s', strtotime($val));
            } elseif ($val === null) {
                $sqlValues[$key] = null;
            } elseif (!is_int($val)) {
                $sqlValues[$key] = trim($val);
            } elseif (is_bool($val)) {
                $sqlValues[$key] = (int)$val;
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
            $this->log->log("Delete-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Delete-Befehl fehlgeschlagen", 0, null, sqlsrv_errors(), $sql, $params);
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
            $this->log->log("Update-Befehl fehlgeschlagen", Log::LEVEL_ERROR, ['error' => sqlsrv_errors(), 'sql' => $sql, 'params' => $params]);
            throw new DatabaseException("Update-Befehl fehlgeschlagen", 0, null, sqlsrv_errors(), $sql, $params);
        }
        return $execute;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionType()
    {
        return DbConnector::CONNECTION_MSSQL;
    }

    /**
     * @inheritDoc
     */
    public function getServerInfo()
    {
        $sqlInfo = sqlsrv_server_info($this->connection);
        return new ServerInfo($sqlInfo['SQLServerName'], DbConnector::CONNECTION_MSSQL, $sqlInfo['SQLServerVersion'], $sqlInfo['CurrentDatabase']);
    }

    /**
     * @inheritDoc
     */
    public function getDatabaseName()
    {
        $sqlInfo = sqlsrv_server_info($this->connection);
        return $sqlInfo['CurrentDatabase'];
    }

    /**
     * @return array|string[]
     * @throws DatabaseException
     */
    public function getDatabases()
    {
        $dbResult = $this->getAll('sys.databases', 'name');
        $result   = [];
        foreach ($dbResult as $dbValue) {
            $result[] = $dbValue['name'];
        }
        return $result;
    }

    /**
     * @param string $table
     * @param array  $fields
     *
     * @return void
     * @throws DatabaseException
     */
    public function createTable(string $table, array $fields)
    {
        $sql = "CREATE TABLE $table (";
        foreach ($fields as $fieldName => $fieldType) {
            $sql .= '[' . $fieldName . '] ' . $fieldType . ',';
        }
        $sql .= ")";
        $this->executeSql($sql);
    }

    /**
     * @inheritDoc
     */
    public function primaryKey()
    {
        return 'int IDENTITY(1,1) PRIMARY KEY';
    }

    /**
     * @inheritDoc
     */
    public function dateTime()
    {
        return 'datetime';
    }

    /**
     * @inheritDoc
     */
    public function date()
    {
        return 'date';
    }

    /**
     * @inheritDoc
     */
    public function decimal(int $length, int $decimals)
    {
        return "decimal($length,$decimals)";
    }

    /**
     * @inheritDoc
     */
    public function string(int $length)
    {
        return 'varchar(' . $length . ')';
    }

    /**
     * @inheritDoc
     */
    public function int()
    {
        return 'int';
    }

    /**
     * @inheritDoc
     */
    public function float()
    {
        return 'float';
    }

    /**
     * @inheritDoc
     */
    public function text()
    {
        return 'text';
    }
    /**
     * @inheritDoc
     */
    public function ping()
    {
        try {
            $result = $this->executeSql("SELECT 1");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
}