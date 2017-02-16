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
        $this->setTable(static::TABLE);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return \Pixie\QueryBuilder\QueryBuilderHandler
     */
    protected function qb()
    {
        return $this->queryBuilder->table($this->getTable());
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
        return $this->qb()->insert($data);
    }
}