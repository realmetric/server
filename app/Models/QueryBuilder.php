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
        $this->timer = \Injectable\ContainerSingleton::get('timer');
        parent::__construct($connection, $grammar, $processor);
    }

    public function get($columns = ['*'])
    {
        $name = 'db select:' . $this->from;
        if (PHP_SAPI !== 'cli'){
            $this->timer->startPoint($name);
        }

        $result = parent::get($columns);

        if (PHP_SAPI !== 'cli'){
            $this->timer->endPoint($name);
        }

        return $result;
    }

    public function insertGetId(array $values, $sequence = null)
    {
        $name = 'db insert:' . $this->from;
        if (PHP_SAPI !== 'cli'){
            $this->timer->startPoint($name);
        }

        $result = parent::insertGetId($values, $sequence);

        if (PHP_SAPI !== 'cli') {
            $this->timer->endPoint($name);
        }

        return $result;
    }

    public function insert(array $values)
    {
        $name = 'db insert:' . $this->from;
        if (PHP_SAPI !== 'cli') {
            $this->timer->startPoint($name);
        }

        $result = parent::insert($values);

        if (PHP_SAPI !== 'cli') {
            $this->timer->endPoint($name);
        }

        return $result;
    }
}