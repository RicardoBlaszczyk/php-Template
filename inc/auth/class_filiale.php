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

    public function resolveFilialeNumber($filialeValue, ?string $firmaNumber, array &$arr_firmen, array &$arr_filialen): ?string
    {
        $filialeValue = trim((string)$filialeValue);
        if ($filialeValue === '') {
            return null;
        }

        $firmaId = null;
        if (!empty($firmaNumber)) {
            foreach ($arr_firmen as $firma) {
                if (!is_array($firma)) {
                    continue;
                }

                if (isset($firma['number']) && (string)$firma['number'] === (string)$firmaNumber) {
                    $firmaId = (int)$firma['ID'];
                    break;
                }
            }
        }

        foreach ($arr_filialen as $filiale) {
            if (!is_array($filiale) || empty($filiale['number'])) {
                continue;
            }

            $sameNumber = (string)$filiale['number'] === $filialeValue;
            $sameFirma  = $firmaId === null || (int)$filiale['firma_id'] === $firmaId;

            if ($sameNumber && $sameFirma) {
                return (string)$filiale['number'];
            }
        }

        foreach ($arr_filialen as $filiale) {
            if (!is_array($filiale) || empty($filiale['name'])) {
                continue;
            }

            $sameName  = mb_strtolower(trim((string)$filiale['name'])) === mb_strtolower($filialeValue);
            $sameFirma = $firmaId === null || (int)$filiale['firma_id'] === $firmaId;

            if ($sameName && $sameFirma) {
                return (string)$filiale['number'];
            }
        }

        $obj_filiale = new filiale();
        $newNumber   = $this->nextMasterDataNumber($arr_filialen);

        $obj_filiale->set_vars([
                                   'name'          => $filialeValue,
                                   'number'        => $newNumber,
                                   'firma_id'      => $firmaId,
                                   'hersteller_id' => 0,
                               ]);
        $obj_filiale->insert();

        $arr_filialen[$obj_filiale->ID] = [
            'ID'            => $obj_filiale->ID,
            'name'          => $filialeValue,
            'number'        => $newNumber,
            'firma_id'      => $firmaId,
            'hersteller_id' => 0,
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