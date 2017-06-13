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

    public function minuteFromDate(string $date)
    {
        $ts = strtotime($date);
        return date('H', $ts) * 60 + date('i', $ts);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function qb()
    {
        $connection = $this->queryBuilder;
        $qb = new QueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        return $qb->from($this->getTable());
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

    public function getById(int $primaryId): array
    {
        return $this->qb()->find($primaryId);
    }


    public function insert(array $data): int
    {
        $insertId = $this->qb()->insertGetId($data);
        if (!$insertId) {
            throw new \Exception('Error in creating record');
        }
        return $insertId;
    }

    public function insertBatch(array $arraysOfData)
    {
        $keys = [];
        $values = [];
        foreach ($arraysOfData as $data) {
            foreach ($data as $key => $value) {
                $keys[] = $key;
                $values[] = $value;
            }
        }

        // Hard but fast
        $placeHolders = array_fill(0, count($keys), '?');
        $placeHolders = implode(',', $placeHolders);
        $valuesSql = array_fill(0, count($values) / count($keys), '(' . $placeHolders . ')');
        $valuesSql = implode(',', $valuesSql);
        $keys = implode(',', $keys);

        $table = $this->getTable();
        $sql = "insert into `{$table}` ({$keys}) values {$valuesSql}";

        $this->queryBuilder->getPdo()
            ->prepare($sql)
            ->execute($values);
    }

    public function insertBatchRaw(array $keys, array $values)
    {
        // Hard but fast
        $placeHolders = array_fill(0, count($keys), '?');
        $placeHolders = implode(',', $placeHolders);
        $valuesSql = array_fill(0, count($values) / count($keys), '(' . $placeHolders . ')');
        $valuesSql = implode(',', $valuesSql);
        $keys = implode(',', $keys);

        $table = $this->getTable();
        $sql = "insert into `{$table}` ({$keys}) values {$valuesSql}";

        $this->queryBuilder->getPdo()
            ->prepare($sql)
            ->execute($values);
    }

    public function increment(int $id, string $column, $amount = 1)
    {
        $this->qb()->where('id', $id)->increment($column, $amount);
    }

    public function getAll($columns = ['*']): array
    {
        return $this->qb()->get($columns);
    }
}