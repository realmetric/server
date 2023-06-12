<?php

namespace App\PdoModelExt;

use PDO;
use PdoModel\PdoModel;

abstract class PdoModelExt extends PdoModel
{
    protected function execute(string $query, array $data = []): bool
    {
//        echo "--------------------\n$query\n";
        return parent::execute($query, $data);
    }

    public function insertIgnore(array $data): int
    {
        if (array_is_list($data)) {
            throw new \Exception('Data keys should be column names, not numbers: ' . json_encode($data));
        }
        $markers = [];
        $values = [];
        $columns = [];
        foreach ($data as $k => $v) {
            $columns[] = "`$k`";
            $markers[] = "?";
            $values[] = $v;
        }
        $sql = 'INSERT OR IGNORE INTO ';
        $sql .= $this->getTable() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $markers) . ")";
        $this->execute($sql, $values);
        return $this->getLastInsertId();
    }

    public function insertUpdateBatch(array $insertRows, array $updateColumns = [], array $incrementColumns = []): bool
    {
        if ($this->isDriverSQLite()) {
            foreach ($insertRows as $row) {
                $this->insertIgnore($row);
            }
            return true;
        }
        return parent::insertUpdateBatch($insertRows, $updateColumns, $incrementColumns);
    }

    protected function isDriverSQLite(): bool
    {
        return strtolower($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) == 'sqlite';
    }
}
