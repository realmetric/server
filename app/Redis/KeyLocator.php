<?php


namespace App\Redis;

/**
 * @property Set track_raw
 * @property SortedSet track_aggr_metrics
 * @property SortedSet track_aggr_slices
 */

class KeyLocator
{
    private $connection;
    private $keys = [
        'track_raw' => 'Set',
        'track_aggr_metrics' => 'SortedSet',
        'track_aggr_slices' => 'SortedSet',
    ];

    public function __construct(\Redis $connection)
    {
        $this->connection = $connection;
    }

    public function __get($name)
    {
        if (isset($this->keys[$name])) {
            $class = '\App\Redis\\' . $this->keys[$name];
            return new $class($name, $this->connection);
        }
    }
}