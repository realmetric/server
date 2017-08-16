<?php


namespace App\Redis;


class Str extends AbstractCommand
{
    public function get():int
    {
        return $this->connection->get($this->key);
    }

    public function set($value, $timeout = 0):bool
    {
        return $this->connection->set($this->key, $value, $timeout);
    }
}