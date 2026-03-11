<?php

class hersteller extends base
{
    public    $log;
    public    $table      = 'KT_HERSTELLER';
    public    $ID         = 0;

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
            'description' => $this->connection->text(),
            'logo' => $this->connection->string(200),
            'css' => $this->connection->string(200),
            'color' => $this->connection->string(50)
        ];
        $this->connection->createTable($this->table, $fields);
    }
}