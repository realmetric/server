<?php


namespace App\Models;

use Illuminate\Database\Query\Builder;

class QueryBuilder extends Builder
{
    public function get($columns = ['*'])
    {
        $timeStart = microtime(true);

        $result = parent::get($columns);

        $time = microtime(true) - $timeStart;
        return $result;
    }
}