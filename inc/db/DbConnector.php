<?php


interface DbConnector
{
    public const CONNECTION_MSSQL = 'MSSQL';
    public const CONNECTION_MYSQL = 'MYSQL';
    public const CONNECTION_ODBC  = 'ODBC';

    /**
     * @return string
     */
    public function primaryKey();

    /**
     * @return string
     */
    public function dateTime();

    /**
     * @return string
     */
    public function date();

    /**
     * @param int $length
     * @param int $decimals
     *
     * @return string
     */
    public function decimal(int $length, int $decimals);

    /**
     * @param int $length
     *
     * @return string
     */
    public function string(int $length);

    /**
     * @return string
     */
    public function text();

    /**
     * @return string
     */
    public function int();

    /**
     * @return string
     */
    public function float();

    /**
     * @param string $tableName
     *
     * @return array
     * @throws DatabaseException
     */
    public function fetchTableColumns(string $tableName);

    /**
     * @param string       $table
     * @param string|array $where
     *
     * @return bool
     * @throws DatabaseException
     */
    public function rowExists(string $table, $where);

    /**
     * @param string $table
     * @param string $field
     *
     * @return array
     * @throws DatabaseException
     */
    public function getDistinctValues(string $table, string $field);

    /**
     * @param bool $withViews
     *
     * @return array
     * @throws DatabaseException
     */
    public function getTableNames($withViews = false);

    /**
     * @param string $table
     * @param array  $data
     *
     * @return int|bool id of inserted element or false if insert failed
     * @throws DatabaseException
     */
    public function insertRow(string $table, array $data);

    /**
     * @param string       $table
     * @param array|string $where
     *
     * @return int
     */
    public function deleteRow(string $table, $where);

    /**
     * @param string       $table
     * @param array|string $where
     * @param array        $data
     *
     * @return int|bool number of affected rows or false if update failed
     * @throws DatabaseException
     */
    public function update(string $table, array $data, $where);

    /**
     * @return string one of the constants defined in DbConnector: CONNECTION_MSSQL | CONNECTION_MYSQL
     */
    public function getConnectionType();

    /**
     * @param string            $table
     * @param string            $select
     * @param null|string|array $where
     * @param null|string       $orderBy
     * @param null|int          $limit
     * @param null|int          $offset
     * @param null|string       $groupBy
     *
     * @return array
     *
     * @throws DatabaseException
     */
    public function getAll(string $table, $select = '*', $where = null, $orderBy = null, $limit = null, $offset = null, $groupBy = null, $whereParams = [], $joinString = '');

    /**
     * @return ServerInfo
     */
    public function getServerInfo();

    /**
     * @param string            $table
     * @param array|string|null $where
     * @param string            $select
     *
     * @return array
     *
     * @throws DatabaseException
     */
    public function getOne(string $table, $where = null, $select = '*');

    /**
     * @param string            $table
     * @param null|string|array $where
     *
     * @return int
     * @throws DatabaseException
     */
    public function count(string $table, $where = null);

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws DatabaseException
     */
    public function tableExists(string $tableName);

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return bool
     * @throws DatabaseException
     */
    public function columnExists(string $tableName, string $columnName);

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array|false
     * @throws DatabaseException
     */
    public function executeSql(string $sql, $params = []);

    /**
     * @return string
     */
    public function getDatabaseName();

    /**
     * @return string[]
     */
    public function getDatabases();

    /**
     * @param string $table
     * @param array  $fields
     *
     * @return bool
     */
    public function createTable(string $table, array $fields);

    /**
     * @return bool
     */
    public function ping();
}