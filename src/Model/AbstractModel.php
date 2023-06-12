<?php

namespace App\Model;

use App\PdoModelExt\PdoModelExt;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;


abstract class AbstractModel extends PdoModelExt
{
    protected Connection $illuminateConnection;

    public function __construct(Connection $connection)
    {
        $this->illuminateConnection = $connection;
        parent::__construct($connection->getPdo());
    }

    protected function qb(): Builder
    {
        $connection = $this->illuminateConnection;
        $qb = new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
        return $qb->from($this->getTable());
    }

    protected function schema(): \Illuminate\Database\Schema\Builder
    {
        return $this->illuminateConnection->getSchemaBuilder();
    }
}
