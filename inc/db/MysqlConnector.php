<?php


class MysqlConnector implements DbConnector
{
    public const FIELD_TYPE_PRIMARYKEY = 'int NOT NULL AUTO_INCREMENT';
    protected $connection;
    protected $log;
    protected $host;

    /**
     * MssqlConnector constructor.
     *
     * @param string          $server
     * @param string          $database
     * @param string          $user
     * @param string          $password
     * @param null|string|int $port
     *
     * @throws JsonException|DatabaseException
     */
    public function __construct(string $server, string $database, string $user, string $password, $port = null, $existingConnection = null)
    {
        $this->host = $server;
        $this->log  = new Log('DB');
        if ($existingConnection !== null) {
            $this->connection = $existingConnection;
            return;
        }
        if (!empty($port)) {
            $port = (int)$port;
        } else {
            $port = null;
        }
        $connection = mysqli_connect($server, $user, $password, $database, $port);
        if ($connection === false) {
            $this->log->log("Fehler bei DB Verbindung", Log::LEVEL_ERROR, mysqli_connect_error());
            throw new DatabaseException("Fehler bei DB Verbindung", 0, null, mysqli_connect_error());
        }
        $this->connection = $connection;

        $this->ensureNoBackslashEscapesSqlMode();
    }

    /**
     * @return void
     * @throws DatabaseException
     * @throws JsonException
     */
    private function ensureNoBackslashEscapesSqlMode(): void
    {
        $sql = "SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'NO_BACKSLASH_ESCAPES')";
        $ok  = mysqli_query($this->connection, $sql);

        if ($ok === false) {
            $this->log->log("Fehler beim Setzen von sql_mode (NO_BACKSLASH_ESCAPES)", Log::LEVEL_ERROR, [
                'error' => mysqli_error($this->connection),
                'sql' => $sql,
            ]);
            throw new DatabaseException(
                "Fehler beim Setzen von sql_mode (NO_BACKSLASH_ESCAPES)",
                0,
                null,
                mysqli_error($this->connection),
                $sql
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchTableColumns(string $tableName)
    {
        $sql    = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?";
        $result = [];
        $data   = $this->fetchData($sql, [$tableName]);
        foreach ($data as $row) {
            $result[] = $row['COLUMN_NAME'];
        }
        return $result;
    }

    public function addTableColumn($tableName, $column, $type, $notNull = null, $default = null)
    {
        $column_definition[] = strtoupper($type);
        $column_definition[] = empty($notNull) ? '' : 'NOT NULL';
        $column_definition[] = $default === null ? '' : 'DEFAULT ' . $default;
        $sql                 = "ALTER TABLE {$tableName} ADD COLUMN {$column} " . implode(' ', $column_definition) . ";";
        $params              = array();
        $this->fetchData($sql, $params);
    }

    public function modifyTableColumn($tableName, $column, $type, $notNull = null, $default = null)
    {
        $column_definition[] = strtoupper($type);
        $column_definition[] = empty($notNull) ? '' : 'NOT NULL';
        $column_definition[] = $default === null ? '' : 'DEFAULT ' . $default;
        $sql                 = "ALTER TABLE {$tableName} MODIFY COLUMN {$column} " . implode(' ', $column_definition) . " ;";
        $params              = array();
        $this->fetchData($sql, $params);
    }

    public function changeTableColumn($tableName, $oldColumnName, $newColumnName, $type, $notNull = null, $default = null)
    {
        $column_definition[] = strtoupper($type);
        $column_definition[] = empty($notNull) ? '' : 'NOT NULL';
        $column_definition[] = $default === null ? '' : 'DEFAULT ' . $default;
        $sql                 = "ALTER TABLE {$tableName} CHANGE COLUMN {$oldColumnName} {$newColumnName} " . implode(' ', $column_definition) . " ;";
        $params              = array();
        $this->fetchData($sql, $params);
    }

    public function dropTableColumn($tableName, $columnName)
    {
        $sql    = "ALTER TABLE {$tableName} DROP COLUMN {$columnName};";
        $params = array();
        $this->fetchData($sql, $params);
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
        if ($result !== false) {
            return $result;
        }
        $this->log->log("Fehler beim 'execute_statement'", Log::LEVEL_ERROR, ['error' => mysqli_error($this->connection), 'sql' => $sql, 'params' => $params]);
        throw new DatabaseException("Fehler beim 'execute_statement'", 0, null, mysqli_error($this->connection), $sql, $params);
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
        $stmt = mysqli_prepare($this->connection, $sql);

        if (!$stmt) {
            $this->log->log("Fehler beim 'prepare_statement'", Log::LEVEL_ERROR, [mysqli_error($this->connection), $sql, $params]);
            throw new DatabaseException("Fehler beim 'prepare_statement'", 0, null, mysqli_error($this->connection), $sql, $params);
        }
        if (!empty($params)) {
            $types = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types[] = 'i';
                } elseif (is_float($param)) {
                    $types[] = 'd';
                } else {
                    $types[] = 's';
                }
            }
            $stmt->bind_param(implode('', $types), ...$params);
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result !== false) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }

            if ($debug) {
                $duration = (microtime(true) - $starTime) * 1000;

                if (!isset($GLOBALS['sql_timings'])) {
                    $GLOBALS['sql_timings'] = [];
                }
                $GLOBALS['sql_timings'][] = [
                    'sql' => $sql,
                    'time' => $duration,
                    'engine' => 'MYSQL'
                ];

                $dbLog    = new Log('Queries', 1);
                $dbLog->log($sql, Log::LEVEL_DEBUG, [['time' => $duration . 'ms', 'params' => $params]]);
            }

            return ['affectedRows' => $stmt->affected_rows, 'lastInsertId' => $stmt->insert_id];
            // return $stmt->insert_id;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDistinctValues(string $table, string $field)
    {
        $table          = str_replace('`', '', $table);
        $table          = mysqli_real_escape_string($this->connection, $table);
        $field          = mysqli_real_escape_string($this->connection, $field);
        $sql            = "SELECT DISTINCT $field FROM `$table`";
        $sqlResult      = $this->fetchData($sql);
        $distinctValues = [];
        foreach ($sqlResult as $result) {
            $distinctValues[] = $result[$field];
        }
        return $distinctValues;
    }

    /**
     * @inheritDoc
     */
    public function count(string $table, $where = null)
    {
        $table  = mysqli_real_escape_string($this->connection, $table);
        $sql    = "SELECT COUNT(*) as count FROM `$table`";
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
            if (stripos($where, 'WHERE') === false) {
                return ['string' => 'WHERE ' . $where, 'params' => []];
            }
            return ['string' => $where, 'params' => []];
        }

        if (is_array($where)) {
            $params = [];
            $clauses = [];

            $like = null;
            $or   = null;
            $in   = null;

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

            $nullParams = [];
            $inParams   = [];

            foreach ($where as $field => $value) {
                if ($value === null) {
                    $nullParams[] = $field;
                    unset($where[$field]);
                } elseif (is_array($value)) {
                    $inParams[$field] = $value;
                    unset($where[$field]);
                }
            }

            // Normale Vergleiche
            foreach ($where as $field => $value) {
                $clauses[] = "`$field` = ?";
                $params[]  = $value;
            }

            // LIKE
            if (!empty($like)) {
                foreach ($like as $field => $value) {
                    $clauses[] = "`$field` LIKE ?";
                    $params[]  = '%' . $value . '%';
                }
            }

            // OR
            if (!empty($or)) {
                $orClauses = [];
                foreach ($or as $field => $value) {
                    $orClauses[] = "`$field` = ?";
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
                    $clauses[] = "`$field` IN ($placeholders)";
                    $params    = array_merge($params, $values);
                }
            }

            // Klassische IN-Arrays
            if (!empty($inParams)) {
                foreach ($inParams as $field => $values) {
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $clauses[] = "`$field` IN ($placeholders)";
                    $params    = array_merge($params, $values);
                }
            }

            // NULL
            if (!empty($nullParams)) {
                foreach ($nullParams as $field) {
                    $clauses[] = "`$field` IS NULL";
                }
            }

            $whereString = 'WHERE ' . implode(' AND ', $clauses);

            return ['string' => $whereString, 'params' => $params];
        }

        return false;
    }


    /**
     * @inheritDoc
     */
    public function getTableNames($withViews = false)
    {
        $sql    = "SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
        $result = [];
        $data   = $this->fetchData($sql);
        foreach ($data as $row) {
            $result[] = $row['TABLE_NAME'];
        }
        return $result;
    }

    public function deleteRow(string $table, $where)
    {
        $sql         = "DELETE FROM `$table`";
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
        return $execute['affectedRows'];
    }

    public function tableExists(string $tableName)
    {
        $where = ['TABLE_NAME' => $tableName, 'TABLE_TYPE' => 'BASE TABLE'];
        if (!empty($this->database)) {
            $where['TABLE_SCHEMA'] = $this->database;
        } else {
            $where['TABLE_SCHEMA'] = MSSQL_DB;
        }
        return $this->rowExists('INFORMATION_SCHEMA.TABLES', $where);
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
        $sql         = "SELECT ";
        $table       = mysqli_real_escape_string($this->connection, $table);
        $sql         .= "$select FROM $table";
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
        if ($limit !== null) {
            $sql .= " LIMIT $limit ";
        }
        if ($offset !== null) {
            $sql .= " OFFSET $offset";
        }
        return $this->fetchData($sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function insertRow(string $table, array $data)
    {
        $insertData   = $this->formatSqlData($data);
        $table        = mysqli_real_escape_string($this->connection, $table);
        $placeHolder  = array_fill(0, count($insertData), '?');
        $fieldString  = implode('` ,`', array_keys($insertData));
        $valuesString = implode(", ", $placeHolder);
        $sql          = "INSERT INTO $table  
                (`$fieldString`)
                VALUES
                ($valuesString)
                ";
        $params       = array_values($insertData);
        $execute      = $this->executeSql($sql, $params);
        if ($execute === false) {
            throw new DatabaseException("Insert-Befehl fehlgeschlagen", 0, null, mysqli_error($this->connection), $sql, $params);
        }
        return $execute['lastInsertId'];
    }

    protected function formatSqlData($data)
    {
        $sqlValues = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $key    = mysqli_real_escape_string($this->connection, $key);
            $isDate = preg_match('/^(\d{2}\.\d{2}\.\d{4}|\d{4}-\d{2}-\d{2})( \d{2}:\d{2}:\d{2})?$/', $val, $output_array);
            if ($isDate) {
                $sqlValues[$key] = date('Y-m-d H:i:s', strtotime($val));
            } elseif (is_string($val)) {
                $sqlValues[$key] = stripslashes(mysqli_real_escape_string($this->connection, trim($val)));
            } elseif (is_bool($val)) {
                $sqlValues[$key] = (int)$val;
            } else {
                $sqlValues[$key] = $val;
            }
        }
        return $sqlValues;
    }

    /**
     * @inheritDoc
     */
    public function update(string $table, array $data, $where)
    {

        $updateData  = $this->formatSqlData($data);
        $table       = mysqli_real_escape_string($this->connection, $table);
        $fieldString = implode('` = ? ,`', array_keys($updateData));

        $sql = "UPDATE `$table` SET
                `$fieldString` = ?";

        $whereResult = $this->buildWhere($where);

        $params = array_values($updateData);
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
            throw new DatabaseException("Update-Befehl fehlgeschlagen", 0, null, sqlsrv_errors(), $sql, $params);
        }
        return $execute['affectedRows'];
        //return $this->affected_rows;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionType()
    {
        return DbConnector::CONNECTION_MYSQL;
    }

    /**
     * @inheritDoc
     */
    public function getServerInfo()
    {
        $version  = mysqli_get_server_info($this->connection);
        $database = $this->getDatabaseName();
        return new ServerInfo($this->host, DbConnector::CONNECTION_MYSQL, $version, $database);
    }

    public function getDatabaseName()
    {
        $databaseResult = $this->executeSql("SELECT DATABASE()");
        return $databaseResult[0]['DATABASE()'];
    }

    public function getDatabases()
    {
        return $this->fetchData('SHOW DATABASES');
    }

    /**
     * @inheritDoc
     */
    public function primaryKey()
    {
        return self::FIELD_TYPE_PRIMARYKEY;
    }

    /**
     * @inheritDoc
     */
    public function dateTime()
    {
        return 'DATETIME';
    }

    /**
     * @inheritDoc
     */
    public function date()
    {
        return 'DATE';
    }

    /**
     * @inheritDoc
     */
    public function decimal(int $length, int $decimals)
    {
        return "DECIMAL($length, $decimals)";
    }

    /**
     * @inheritDoc
     */
    public function string(int $length)
    {
        return "VARCHAR($length)";
    }

    /**
     * @inheritDoc
     */
    public function int()
    {
        return "INT";
    }

    /**
     * @inheritDoc
     */
    public function float()
    {
        return "FLOAT";
    }

    /**
     * @inheritDoc
     */
    public function text()
    {
        return "TEXT";
    }

    /**
     * @inheritDoc
     */
    public function createTable(string $table, array $fields)
    {
        $sql = "CREATE TABLE `$table` (";
        foreach ($fields as $fieldName => $fieldType) {
            $sql .= '`' . $fieldName . '` ' . $fieldType . ',';
        }
        $sql = rtrim($sql, ',');
        preg_match('/\(`(.*)` ' . self::FIELD_TYPE_PRIMARYKEY . '/', $sql, $pKey);
        if (!empty($pKey) && !empty($pKey[1])) {
            $sql .= ", PRIMARY KEY (`$pKey[1]`)";
        }
        $sql .= ")";
        if (!$this->executeSql($sql)) {
            $this->log->log("Fehler beim anlegen der Tabelle $table", Log::LEVEL_ERROR, [$sql, mysqli_error($this->connection)]);
            throw new DatabaseException("Fehler beim anlegen der Tabelle $table", 0, null, mysqli_error($this->connection));
        }
    }

    /**
     * Drops a table from the database.
     *
     * @param string $table The name of the table to drop.
     *
     * @return void
     * @throws DatabaseException If the SQL execution fails.
     *
     * @inheritDoc
     */
    public function dropTable(string $table)
    {
        $sql = "DROP TABLE [$table] ";
        $this->executeSql($sql);
    }

    /**
     * Truncates a table by deleting all rows.
     *
     * @param string $table The name of the table to truncate.
     *
     * @return void
     */
    public function truncateTable(string $table)
    {
        $sql = "TRUNCATE TABLE [$table] ";
        $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return bool
     * @throws DatabaseException
     */
    public function columnExists(string $tableName, string $columnName)
    {
        $sql = "SELECT * 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_name = '" . $tableName . "' 
            AND column_name = '" . $columnName . "';";
        $result = $this->executeSql($sql);
        return !empty($result) ? true : false;
    }

    /**
     * @inheritDoc
     */
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