<?php


namespace App\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property \App\Timer timer
 */
class QueryBuilder extends Builder
{
    private $timer;

    public function __construct($connection, $grammar, $processor)
    {
        $this->timer = (\App\App::getContainer())->get('timer');
        parent::__construct($connection, $grammar, $processor);
    }

    public function get($columns = ['*'])
    {
        $name = 'mysql_' . mt_rand(99, 9999);
        $this->timer->startPoint($name);

        $result = parent::get($columns);

        $this->timer->endPoint($name);
        return $result;
    }
}