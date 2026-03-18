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

    public function resolveFirmaNumber($firmaValue, array &$arr_firmen): ?string
    {
        $firmaValue = trim((string)$firmaValue);
        if ($firmaValue === '') {
            return null;
        }

        foreach ($arr_firmen as $firma) {
            if (!is_array($firma)) {
                continue;
            }

            if (isset($firma['number']) && (string)$firma['number'] === $firmaValue) {
                return (string)$firma['number'];
            }
        }

        foreach ($arr_firmen as $firma) {
            if (!is_array($firma) || empty($firma['name'])) {
                continue;
            }

            if (mb_strtolower(trim((string)$firma['name'])) === mb_strtolower($firmaValue)) {
                return (string)$firma['number'];
            }
        }

        $obj_firma = new firma();
        $newNumber = $this->nextMasterDataNumber($arr_firmen);

        $obj_firma->set_vars([
                                 'name'    => $firmaValue,
                                 'number'  => $newNumber,
                                 'account' => null,
                             ]);
        $obj_firma->insert();

        $arr_firmen[$obj_firma->ID] = [
            'ID'      => $obj_firma->ID,
            'name'    => $firmaValue,
            'number'  => $newNumber,
            'account' => null,
        ];

        return $newNumber;
    }

    protected function nextMasterDataNumber(array $rows): string
    {
        $maxNumber = 0;

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['number'])) {
                continue;
            }

            $number = trim((string)$row['number']);
            if (ctype_digit($number)) {
                $maxNumber = max($maxNumber, (int)$number);
            }
        }

        return str_pad((string)($maxNumber + 1), 2, '0', STR_PAD_LEFT);
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