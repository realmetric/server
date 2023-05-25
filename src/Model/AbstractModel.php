<?php

namespace App\Model;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use PdoModel\PdoModel;

abstract class AbstractModel
{
    const TABLE = null;
    private $tableName = null;
    private $connection = null;
    private PdoModel $pdoModel;

    public function __construct(Connection $connection)
    {
        $this->pdoModel = new PdoModel($connection->getPdo());
        if (!static::TABLE) {
            throw new \Exception('Model ' . static::class . ' without TABLE constant');
        }
        $this->setTable(static::TABLE);
        $this->connection = $connection;
    }

    protected function qb()
    {
        $connection = $this->connection;
        $qb = new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
        return $qb->from($this->getTable());
    }

    protected function shema()
    {
        return $this->connection->getSchemaBuilder();
    }

    public function setTable($name): static
    {
        $this->tableName = $name;
        $this->pdoModel->setTable($name);
        return $this;
    }

    protected function getTable()
    {
        return $this->tableName;
    }

    protected function getById(int $primaryId): array
    {
        return $this->qb()->find($primaryId);
    }

    protected function insert(array $data): int
    {
        $insertId = $this->qb()->insertGetId($data);
        if (!$insertId) {
            throw new \Exception('Error in creating record');
        }
        return $insertId;
    }

    public function insertOrIncrementBatch(array $insertRows)
    {
        return $this->pdoModel->insertUpdateBatch($insertRows, incrementColumns: ['value']);
    }

    public function getAll($columns = ['*']): array
    {
        return $this->qb()->get($columns)->all();
    }
}
