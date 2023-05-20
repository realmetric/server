<?php

namespace App\Model;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

abstract class AbstractModel
{
    const TABLE = null;
    const MAX_PREPARED_STMT_COUNT = 60000;
    const MAX_TABLE_NAME_EXISTS_CACHE_COUNT = 20;

    protected $tableNameExistsCache = [];

    private $tableName = null;
    private $connection = null;

    public function __construct(Connection $connection)
    {
        if (!static::TABLE) {
            throw new \Exception('Model ' . static::class . ' without TABLE constant');
        }
        $this->setTable(static::TABLE);
        $this->connection = $connection;
    }

    public function minuteFromDate(string $date)
    {
        $ts = strtotime($date);
        return date('H', $ts) * 60 + date('i', $ts);
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

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function shema()
    {
        return $this->connection->getSchemaBuilder();
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
        $keys = array_keys(reset($arraysOfData));
        $values = [];

        foreach ($arraysOfData as $data) {
            foreach ($data as $key => $value) {
                $values[] = $value;
            }
        }

        $this->insertBatchRaw($keys, $values);
    }

    public function insertBatchRaw(array $keys, array $values)
    {
        // Hard but fast
        $keysCount = count($keys);
        $valuesCount = count($values);
        $valuesSqlCount = $valuesCount / $keysCount;
        $valuesSqlChunkSize = (int) floor(self::MAX_PREPARED_STMT_COUNT / $keysCount);
        $valuesChunkSize = $valuesSqlChunkSize * $keysCount;

        $placeHolders = array_fill(0, $keysCount, '?');
        $placeHolders = implode(',', $placeHolders);
        $valuesSql = array_fill(0, $valuesSqlCount, '(' . $placeHolders . ')');

        $keys = implode(',', $keys);
        $table = $this->getTable();

        $valuesOffset = 0;
        foreach(array_chunk($valuesSql, $valuesSqlChunkSize) as $valuesSqlPart){
            $valuesPart = array_slice($values, $valuesOffset * $valuesSqlChunkSize * $keysCount, $valuesChunkSize);
            $valuesOffset++;
            $valuesSqlPart = implode(',', $valuesSqlPart);

            $sql = "insert into `{$table}` ({$keys}) values {$valuesSqlPart}";
            
            $this->connection->getPdo()
                ->prepare($sql)
                ->execute($valuesPart);
        }
    }

    public function increment(int $id, string $column, $amount = 1)
    {
        $this->qb()->where('id', $id)->increment($column, $amount);
    }

    public function getAll($columns = ['*']): array
    {
        return $this->qb()->get($columns)->all();
    }

    public function truncate()
    {
        $this->qb()->truncate();
    }

    abstract protected function createTable($name);

    protected function createTableIfNotExists()
    {
        $tableName = $this->getTable();
        if (!in_array($tableName, $this->tableNameExistsCache, true)){
            if (count($this->tableNameExistsCache) > static::MAX_TABLE_NAME_EXISTS_CACHE_COUNT){
                $this->tableNameExistsCache = [];
            }

            $this->createTable($tableName);
            $this->tableNameExistsCache[] = $tableName;
        }

        return $this;
    }
}
