<?php

namespace App\Models;

abstract class AbstractModel
{
    const TABLE = null;
    private $tableName = null;
    private $queryBuilder = null;

    public function __construct($queryBuilder)
    {
        $this->setTable(self::TABLE);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return \Pixie\QueryBuilder\QueryBuilderHandler
     */
    protected function qb()
    {
        return $this->queryBuilder->table($this->getTable());
    }

    protected function setTable($name)
    {
        $this->tableName = $name;
    }

    protected function getTable()
    {
        return $this->tableName;
    }

    // ------------- Base public functions below -----------------
    public function getById($primaryId)
    {
        return $this->qb()->find($primaryId);
    }


    public function insert($data)
    {
        return $this->qb()->insert($data);
    }
}