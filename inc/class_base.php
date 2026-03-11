<?php

class base
{
    var $arr_sysfields   = [
        'ID',
        'ERSTELLTVON',
        'ERSTELLTAM',
        'UPDATEVON',
        'UPDATEAM',
        'GELOESCHT',
    ];
    var $arr_need_length = [
        'varchar',
    ];
    var $connection;
    var $connectionType;
    var $user            = '';

    protected static $checkedTables    = false;
    public static    $tableColumns     = [];
    public static    $tableColumnTypes = [];

    public $arr_cols       = [];
    public $arr_datefields = [];

    function __construct($table = '')
    {
        global $connection, $connectionType;

        $this->connection     = $connection;
        $this->connectionType = $connectionType;

        $this->log = new Log('DB');

        $this->user = !empty(@$_SESSION['session_user']) ? @$_SESSION['session_user'] : 'System';

        $this->set_table($table);


        if (!empty($table)) {
            $tableExists = $this->connection->tableExists($this->table);
            if (!$tableExists) {
                $this->createDatabaseTable();
            }

            $this->get_felder(str_replace(array('[', ']', '.', 'dbo'), '', $table));
            $this->get_columnTypes($table);
        }
    }

    /**
     * Setzen der Tabelle
     *
     * @param $table string Tabellenname
     *
     * @return void
     */
    function set_table($table)
    {
        $this->table = $table;
    }

    /**
     * Setzen der POST-Daten in ein Object
     *
     * @param $arr_post array Spalten name => value
     *
     * @return void
     */
    function set_vars($arr_post)
    {
        foreach ($arr_post as $key => $val) {
            $new_key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if (is_array($val)) {
                if (!empty($val)) {
                    $flags   = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
                    $new_val = json_encode($val, $flags);
                } else {
                    $new_val = null;
                }
            } else if ($val == '0000-00-00 00:00:00') {
                $new_val = null;
            } else {
                $new_val = $val;
            }
            $this->$new_key = $new_val;
        }
    }

    /**
     * Prüfen der Daten (GLOBAL)
     * In der Basisklasse werden die Werte auf den DATA_TYPE geprüft.
     *
     * @return array Fehler|Spalten die den falschen Typen haben
     * @throws DatabaseException
     */
    function check_data()
    {
        $arr_error = [];
        if (empty(static::$tableColumns[$this->table])) {
            $this->get_felder($this->table);
        }
        //$this->log->log(json_encode(static::$tableColumns[$this->table]));
        foreach (static::$tableColumns[$this->table] as $id => $columnvalue) {

            $columnname = strtoupper($columnvalue['COLUMN_NAME']);
            if (empty($this->$columnname)) {
                $this->$columnname = null;
                continue;
            }
            if (!in_array($columnname, $this->arr_sysfields)) {

                switch ($columnvalue['DATA_TYPE']) {
                    case 'bit':
                        /**
                         * if (!is_bool($this->$columnname)) {
                         * $arr_error[] = "Wert: " . $columnvalue['COLUMN_NAME'] . " erwartet [bit]. Erhalten: " . $this->$columnname;
                         * }
                         **/
                        break;
                    case 'int':
                    case 'float':
                    case 'decimal':
                        if (!empty($this->$columnname) && !is_numeric($this->$columnname)) {
                            $arr_error[] = "Wert: " . $columnvalue['COLUMN_NAME'] . " erwartet [int/float/decimal]. Erhalten: " . $this->$columnname;
                        }
                        break;
                    case 'nvarchar':
                    case 'varchar':
                    case 'text':
                        if (!empty($this->$columnname) && (!is_string($this->$columnname) && !is_numeric($this->$columnname))) {
                            $this->log->log($columnvalue['DATA_TYPE']);
                            $arr_error[] = "Wert: " . $columnvalue['COLUMN_NAME'] . " erwartet [string/longstring]. Erhalten: " . $this->$columnname;
                        } else {
                            $this->$columnname = null;
                        }
                        break;
                    case 'datetime':
                        if (!empty($this->$columnname)) {
                            // In DateTime-Objekt konvertieren
                            $date              = new DateTime($this->$columnname);
                            $this->$columnname = $date->format('Y-m-d H:i:s'); // → 2025-05-14 11:47:29
                            $isDate            = preg_match('/^(\d{2}\.\d{2}\.\d{4}|\d{4}-\d{2}-\d{2})( \d{2}:\d{2}:\d{2})?(\.\d{1,6}|\+\d{2}:\d{2})?$/', $this->$columnname, $output_array);
                            if (!empty($this->$columnname) && !$isDate) {
                                $arr_error[] = "Wert: " . $columnvalue['COLUMN_NAME'] . " erwartet [datetime]. Erhalten: " . $this->$columnname;
                                // var_dump(strtotime($this->$columnname));
                            }
                        }
                        break;
                }
            }
        }
        return $arr_error;
    }

    function load()
    {
        $arr_result = $this->connection->getOne($this->table, "ID = '" . $this->ID . "'", '*');

        if ($arr_result) {
            foreach ($arr_result as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Insert-Statement in die Datenbank-Tabelle
     *
     * @return void
     * @throws DatabaseException
     */
    function insert()
    {
        $arr_felder = $this->get_felder($this->table);

        $arr_fields = [
            'ERSTELLTAM'  => date('Y-m-d H:i:s'),
            'ERSTELLTVON' => $this->user,
            'GELOESCHT'   => 0
        ];
        foreach ($arr_felder as $key => $val) {
            if (!in_array($val['COLUMN_NAME'], $this->arr_sysfields)) {
                $colName = $val['COLUMN_NAME'];
                if ($colName != 'ID') {
                    $arr_fields[$colName] = $this->$colName ?? null;
                }
            }
        }

        $this->ID = $this->connection->insertRow($this->table, $arr_fields);
    }

    /**
     * Update-Statement in die Datenbank-Tabelle
     *
     * @return void
     * @throws DatabaseException
     */
    function update()
    {
        $arr_felder = $this->get_felder($this->table);
        $arr_fields = [
            'UPDATEAM'  => date('Y-m-d H:i:s'),
            'UPDATEVON' => $this->user,
        ];
        foreach ($arr_felder as $key => $val) {
            if (!in_array($val['COLUMN_NAME'], $this->arr_sysfields)) {
                $colName = $val['COLUMN_NAME'];
                if ($colName != 'ID') {
                    $arr_fields[$colName] = @$this->$colName;
                }
            }
        }
        $this->connection->update($this->table, $arr_fields, ['ID' => $this->ID]);
    }

    /**
     * Delete-Statement auf die Datenbank-Tabelle
     *
     * @param $id int|string ID des Datenbankeintrags
     *
     * @return void
     * @throws DatabaseException
     */
    function delete($id = 0)
    {
        if (!empty($id)) {
            $this->ID = $id;
        }

        if (!defined('MSSQL_SOFTDELETE') || MSSQL_SOFTDELETE === '1') {
            $this->connection->update($this->table, ['GELOESCHT' => '1', 'UPDATEAM' => date('Y-m-d H:i:s'), 'UPDATEVON' => $this->user], ['ID' => $this->ID]);
        } else {
            $this->connection->deleteRow($this->table, ['ID' => $this->ID]);
        }
        return true;
    }

    /**
     * Holen der zuletzt eingefügten ID
     *
     * @return string|int Die zuletzt eingefügte ID
     * @throws DatabaseException
     */
    function last_insert_id()
    {
        $result = $this->connection->getOne($this->table, null, 'MAX(id) as last_insert_id');
        return $result['last_insert_id'];
    }

    /**
     * Holen eines ganzen Datensatzes
     *
     * @param array|null  $and       Weitere WHERE-Bedingungen
     * @param string|null $order     ORDER-Bedingungen
     * @param int|null    $limit     LIMIT-Bedingungen
     * @param int|null    $offset    OFFSET-Bedingungen
     * @param string|null $increment Incrementfield, falls es von ID abweicht
     *
     * @return array Datensatz|error
     * @throws DatabaseException
     */
    function get_datensatz($and = null, string $order = null, int $limit = null, int $offset = null, string $increment = null)
    {

        $arr_return          = [];
        $str_increment_field = 'ID';
        if (!empty($increment)) {
            $str_increment_field = $increment;
        } else {
            if (is_array($and)) {
                if (in_array('GELOESCHT', $this->arr_sysfields)) {
                    if (!array_key_exists_recursive('GELOESCHT', $and)) {
                        $notDeleted = array('GELOESCHT' => 0);
                        $and        = array_merge($notDeleted, $and);
                    }
                }
            } else {
                if (!empty($and) && strpos('GELOESCHT', $and) !== false) {
                    // KEIN Gelöscht setzen
                } else {
                    $and .= (!empty($and) ? " AND " : '') . " COALESCE(GELOESCHT,0) = 0 ";
                }
            }
        }

        $result = $this->connection->getAll($this->table, '*', $and, $order, $limit, $offset);
        if (!empty($result)) {
            foreach ($result as $row) {
                // Wenn ID nicht das Incrementfield ist muss die Funktion überlagert werden und das Incrementfield mitgegeben werden.
                $arr_return[$row[$str_increment_field]] = $row;
            }
        } else {
            $arr_return['error'] = 'Datentabelle entspricht nicht den technischen Voraussetzungen zur Anzeige!<br/>';
        }
        return $arr_return;
    }

    /**
     * Datensätze Anzahl
     *
     * @param array|null  $and
     * @param string|null $increment
     *
     * @return int|mixed
     * @throws DatabaseException
     */
    function get_datensatz_count(array $and = null, string $increment = null)
    {
        $int_return          = 0;
        $str_increment_field = 'ID';
        if (!empty($increment)) {
            $str_increment_field = $increment;
        } else {
            if (is_array($and)) {
                if (in_array('GELOESCHT', $this->arr_sysfields)) {
                    if (!array_key_exists_recursive('GELOESCHT', $and)) {
                        $notDeleted = array('GELOESCHT' => 0);
                        $and        = array_merge($notDeleted, $and);
                    }
                }
            } else {
                if (!empty($and) && strpos('GELOESCHT', $and) !== false) {
                    // KEIN Gelöscht setzen
                } else {
                    $and .= (!empty($and) ? " AND " : '') . " COALESCE(GELOESCHT,0) = 0 ";
                }
            }
        }
        $and    = empty($and) ? null : $and;
        $result = $this->connection->getAll($this->table, 'COUNT(' . $str_increment_field . ') as amount', $and);
        if (!empty($result)) {
            foreach ($result as $row) {
                // Wenn ID nicht das Incrementfield ist muss die Funktion überlagert werden und das Incrementfield mitgegeben werden.
                $int_return = $row['amount'];
            }
        } else {
            $int_return['error'] = 'Datentabelle entspricht nicht den technischen Voraussetzungen zur Anzeige!<br/>';
        }

        return $int_return;
    }



    ############################################################################
    ## Bas Class-Fields
    ############################################################################

    /**
     * Holen des INFORMATION_SCHEMA.COLUMNS einer Tabelle
     *
     * @param string     $table Tabellenname
     * @param array|null $and   Weitere WHERE-Bedingungen
     *
     * @return array INFORMATION_SCHEMA.COLUMNS
     * @throws DatabaseException
     */
    function get_felder(string $table, array $arr_and = array())
    {
        $arr_felder = [];
        if (isset(static::$tableColumns[$this->table])) {
            return static::$tableColumns[$this->table];
        }

        if (defined('MSSQL_DB') && strtoupper($this->connectionType) == 'MYSQL') {
            $arr_and = array_merge(['TABLE_NAME' => $table, 'TABLE_SCHEMA' => MSSQL_DB], $arr_and);
        } else {
            $arr_and = array_merge(['TABLE_NAME' => $table], $arr_and);
        }
        $result = $this->connection->getAll('INFORMATION_SCHEMA.COLUMNS', '*', $arr_and);
        foreach ($result as $row) {
            $arr_felder[]     = $row;
            $this->arr_cols[] = $row['COLUMN_NAME'];
            if ($row['DATA_TYPE'] == 'datetime') {
                $this->arr_datefields[] = $row['COLUMN_NAME'];
            }
        }
        static::$tableColumns[$this->table] = $arr_felder;
        return $arr_felder;
    }

    /**
     * Holen der Column-Types
     *
     * @param string $table Tabellenname
     *
     * @return array Column-Types Columnname => DATA_TYPE
     */
    function get_columnTypes($table)
    {
        $arr_columnTypes = [];

        if (isset($this->databaseMigration)) {
            foreach ($this->databaseMigration as $column => $type) {
                if (!in_array($column, $this->arr_cols) && !$this->connection->columnExists($this->table, $column)) {
                    $this->connection->addTableColumn($this->table, $column, $type, false);
                    $arr_columnTypes[$column] = $type;
                }
            }
        }

        foreach (static::$tableColumns[$table] as $row) {
            $arr_columnTypes[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
        }
        static::$tableColumnTypes[$table] = $arr_columnTypes;
        return $arr_columnTypes;
    }

    /**
     * @param        $url
     * @param        $parameters
     * @param string $verb
     * @param bool   $auth
     * @param bool   $data
     *
     * @return bool|string
     * @throws JsonException
     */
    public function do_curl($url, $parameters, $verb = 'GET', $auth = true, $data = false)
    {

        $query_str = '';

        if (is_array($parameters) && !empty($parameters)) {
            $query_str = '?' . http_build_query($parameters);
        }

        $curl = curl_init();

        $this->log->log('Url [do_curl] ' . $url . $query_str, 'DEBUG', [$data]);

        $options = [
            CURLOPT_URL            => $url . $query_str,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $verb,
        ];
        /**
         * Options for the request.
         *
         * @var array $options
         */
        $options = $this->setHeaderForCurlOptions($options, $data);
        $options = $this->setPostfieldsForCurlOptions($options, $data);
        $options = $this->setProxyForCurlOptions($options);
        $options = $this->setAuthForCurlOptions($options, $auth);
        $this->log->log('Request [do_curl] ' . $url . $query_str, 'DEBUG', [$options]);

        // $additional_info['log'] = $options;
        // $this->log->log(json_encode('Options [do_curl]'), 'DEBUG', $additional_info);
        curl_setopt_array($curl, $options);

        $this->log->log('Start [do_curl] ' . $url . $query_str, 'DEBUG');

        $response  = curl_exec($curl);
        $error     = curl_error($curl);
        $info      = curl_getinfo($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($verb == 'POST') {
            // echo '<pre>';
            // print_r($response);
            // echo '</pre>';
            // die();
        }

        curl_close($curl);

        $arr_log = array(
            'error'     => $error,
            'info'      => $info,
            'http_code' => $http_code,
            'response'  => $response);

        if (!empty($error)) {
            $additional_info['log'] = $arr_log;
            // $response               = $arr_log;
            $this->log->log('Fehler in Request [do_curl]', 'ERROR', $additional_info);
        } else {
            $additional_info['log'] = $arr_log;
            $this->log->log('Erfolgreicher Request [do_curl]', 'INFO', $additional_info);
        }
        return $response;
    }


    private function setHeaderForCurlOptions($options, $data)
    {
        $headers = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Cache-Control: no-cache',
            // 'Expect: 100-continue',
            'Connection: keep-alive',
            //'Cookie: PHPSESSID=' . session_id()
        );

        $options[CURLOPT_HTTPHEADER] = $headers;

        return $options;
    }

    /**
     * @param $options
     * @param $auth
     *
     * @return mixed
     */
    private function setAuthForCurlOptions($options, $auth)
    {
        if ($auth === true) {

            if (empty($this->curlUser) && empty($this->curlPass)) {
                $this->curlUser = defined('API_USER') ? API_USER : 'sa';
                $this->curlPass = defined('API_PASS') ? API_PASS : 'kaitech';
            }

            // Variante 1: klassisch über CURLOPT_USERPWD
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD]  = $this->curlUser . ':' . $this->curlPass;

            // Variante 2: Basic Auth im Header (falls CURLOPT_USERPWD nicht genutzt werden soll)
            /*
            $basicAuthHeader = 'Authorization: Basic ' . base64_encode($this->curlUser . ':' . $this->curlPass);
            if (!isset($options[CURLOPT_HTTPHEADER])) {
                $options[CURLOPT_HTTPHEADER] = [];
            }
            $options[CURLOPT_HTTPHEADER][] = $basicAuthHeader;
            */
        }
        return $options;
    }

    /**
     * @param $options
     * @param $data
     *
     * @return mixed
     */
    private function setPostfieldsForCurlOptions($options, $data)
    {
        if (!empty($data)) {
            $options[CURLOPT_POST] = true;

            // JSON korrekt kodieren
            $payload = is_array($data)
                ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : json_encode(json_decode($data, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $options[CURLOPT_POSTFIELDS] = $payload;

            // Ganz wichtig: Header setzen
            if (!isset($options[CURLOPT_HTTPHEADER])) {
                $options[CURLOPT_HTTPHEADER] = [];
            }
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json; charset=utf-8';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($payload);
        }

        return $options;
    }

    /**
     * @param $options
     *
     * @return mixed
     */
    private function setProxyForCurlOptions($options)
    {
        if (defined('LINK_USE_PROXY_GENESYS') && LINK_USE_PROXY_GENESYS === 'aktiv') {
            $options[CURLOPT_PROXY] = defined('DIVA_PROXY') && defined('DIVA_PROXYPORT') ? DIVA_PROXY . ':' . DIVA_PROXYPORT : false;

            if (defined('DIVA_PROXYUSER') && !empty(DIVA_PROXYUSER)) {
                $options[CURLOPT_PROXYUSERPWD] = defined('DIVA_PROXYUSER') && defined('DIVA_PROXYPASS') ? DIVA_PROXYUSER . ':' . DIVA_PROXYPASS : false;
            }
        }
        return $options;
    }


    /**
     * @param $value
     */
    public function output($value)
    {
        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) == 'cgi') {
            if (is_array($value)) {
                echo '<pre>';
                print_r($value);
                echo '<pre>';
            } else {
                echo $value . '<br/>';
            }
        } else {
            if (is_array($value)) {
                echo json_encode($value) . "\n";
            } else {
                echo strip_tags($value) . "\n";
            }
        }
    }

    /**
     * @return bool|void
     * @throws DatabaseException
     */
    protected function check_tables()
    {
        if (self::$checkedTables) {
            return true;
        }
        $ergebnis = false;

        if ($this->connection->tableExists($this->table)) {
            $ergebnis = true;
        }

        self::$checkedTables = $ergebnis;
        return $ergebnis;
    }

    /**
     * @param $sql_create
     *
     * @return bool
     * @throws DatabaseException
     */
    protected function create_table($sql_create)
    {
        $sql = strip_tags($sql_create);

        if ($this->connection->executeSql($sql)) {
            $ergebnis = true;
            $this->log->log("[" . get_class($this) . "][" . __FUNCTION__ . "] " . $this->table . " created");
        } else {
            $ergebnis = false;
            $this->log->log("[" . get_class($this) . "][" . __FUNCTION__ . "] " . $this->table . " = Fehler bei der Anlage Tabelle! ", 'ERROR');
        }
        return $ergebnis;
    }


    /**
     * @return string
     */
    public function analyse_value($value)
    {
        if (preg_match('/\A\d+\z/', $value)) {
            $return = 'int';
        } else if (preg_match('/\A\d+[.,]\d+\z/', $value)) {
            $return = 'float';
        } else if (preg_match('/\A\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\z/', $value)) {
            $return = 'datetime';
        } else if (is_array(json_decode($value, true))) {
            $return = 'varchar(MAX)';
        } else {
            $return = 'varchar(255)';
        }

        return $return;
    }

    /**
     * @param $col_name
     * @param $col_type
     * @param $length
     * @param $null
     * @param $default
     *
     * @return array
     * @throws DatabaseException
     */
    public function add_database_col($col_name, $col_type, $length = null, $null = null, $default = null)
    {

        $result                    = array();
        $arr_add_column[$col_name] = "ALTER TABLE " . $this->table . " ADD " . $col_name
            . " " . $col_type
            . (!empty($length) ? "(" . $length . ") " : " ")
            . ($null == 'NO' && $length > 1 ? "NOT NULL " : "NULL ")
            . (!empty($default) ? "DEFAULT " . $default : "");

        if (!empty($arr_add_column)) {
            foreach ($arr_add_column as $col => $sql) {
                $result = $this->addColumnSql($col, $sql);
            }
        }
        return $result;
    }

    /**
     * @param $columnName
     * @param $sql
     *
     * @return array|false|int|string[]
     * @throws DatabaseException
     */
    private function addColumnSql($columnName, $sql)
    {

        if (!empty($sql) && $this->connection->columnExists($this->table, $columnName) === false) {
            $result = $this->connection->executeSql($sql);
        } else {
            $result = array('error' => 'Kein SQL-Statement vorhanden!');
        }
        return $result;
    }
}
