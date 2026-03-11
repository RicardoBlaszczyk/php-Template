<?php

class filiale extends base
{
    public $log;
    public $table = 'KT_FILIALE';
    public $ID    = 0;

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


    public function load_by_number($number, $firma_id)
    {
        $and = " number = '" . $number . "' AND firma_id = '" . $firma_id . "' ";

        if ($this->connection->columnExists($this->table, 'number')) {
            $result = $this->connection->getOne($this->table, $and);
        } else {
            $result = array('error' => 'Spalte Number nicht gefunden');
        }
        if (!empty($result) && !isset($result['error'])) {
            $this->ID = $result['ID'];
            $this->load();
        }
        return $result;
    }

    protected function createDatabaseTable()
    {
        $fields = [
            'ID'            => $this->connection->primaryKey(),
            'ERSTELLTAM'    => $this->connection->datetime(),
            'ERSTELLTVON'   => $this->connection->string(50),
            'UPDATEAM'      => $this->connection->datetime(),
            'UPDATEVON'     => $this->connection->string(50),
            'GELOESCHT'     => $this->connection->int(),
            'name'          => $this->connection->string(100),
            'number'        => $this->connection->string(3),
            'firma_id'      => $this->connection->int(),
            'hersteller_id' => $this->connection->int()
        ];
        $this->connection->createTable($this->table, $fields);
    }
}