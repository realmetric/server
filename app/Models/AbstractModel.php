<?php
declare(strict_types = 1);

namespace App\Models;

abstract class AbstractModel
{
    const TABLE = null;
    private $tableName = null;
    private $queryBuilder = null;

    public function __construct($queryBuilder)
    {
        if (!static::TABLE) {
            throw new \Exception('Model ' . static::class . ' without TABLE constant');
        }
        $this->setTable(static::TABLE);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function qb()
    {
        return $this->queryBuilder->query()->from($this->getTable());
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function shema()
    {
        return $this->queryBuilder->getSchemaBuilder();
    }

    public function setTable($name)
    {
        $this->tableName = $name;
        return $this;
    }

    public function getTable()
    {
        return $this->tableName;
    }

    // ------------- Base public functions below -----------------

    public function getById(int $primaryId):array
    {
        return $this->qb()->find($primaryId);
    }


    public function insert(array $data):int
    {
        $insertedId = $this->qb()->insert($data);
        if (!$insertedId) {
            throw new \Exception('Error in creating record');
        }
        return (int)$insertedId;
    }

    public function getAll():array
    {
        return $this->qb()->get();
    }
}