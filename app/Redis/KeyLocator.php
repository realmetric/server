<?php


namespace App\Redis;

/**
 * @property Set track_raw
 */


class KeyLocator
{
    private $connection;
    private $keys = [
        'track_raw' => 'Set',
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