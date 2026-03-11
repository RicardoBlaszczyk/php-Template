<?php

class firma extends base
{
    public $log;
    public $table = 'KT_FIRMA';
    public $ID = 0;

    public function __construct($ID = 0)
    {
        parent::__construct($this->table);

        $this->log = new Log($this->table);

        if ($ID > 0) {
            $this->ID = $ID;
            $this->load();
        } else {
            $this->ID = 0;
        }
    }


    public function load_by_number($number)
    {
        $and = " number = '" . $number . "'";

        if ($this->connection->columnExists($this->table, 'number')) {
            $result = $this->connection->getOne($this->table, $and);
        } else {
            $result = array('error' => 'Spalte Number nicht gefunden');
        }
        if (!empty($result) && !isset($result['error'])) {
            $this->ID = $result['ID'];
            $this->load();
        } else {
            // var_dump($result);
        }
        return $result;
    }

    protected function createDatabaseTable()
    {
        $fields = [
            'ID' => $this->connection->primaryKey(),
            'ERSTELLTAM' => $this->connection->datetime(),
            'ERSTELLTVON' => $this->connection->string(50),
            'UPDATEAM' => $this->connection->datetime(),
            'UPDATEVON' => $this->connection->string(50),
            'GELOESCHT' => $this->connection->int(),
            'name' => $this->connection->string(100),
            'number' => $this->connection->string(3),
            'account' => $this->connection->string(100),
        ];
        $this->connection->createTable($this->table, $fields);
    }
}