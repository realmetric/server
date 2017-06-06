<?php


namespace App\Redis;


class Set extends AbstractCommand
{
    public function sAdd($value):int
    {
        return $this->connection->sAdd($this->key, $value);
    }

    public function sCard():int
    {
        return $this->connection->sCard($this->key);
    }

    public function sPop()
    {
        return $this->connection->sPop($this->key);
    }
}