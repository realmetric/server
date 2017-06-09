<?php


namespace App\Redis;


class SortedSet extends AbstractCommand
{
    public function zAdd(string $member, float $score):int
    {
        return $this->connection->zAdd($this->key, $score, $member);
    }

    public function zIncrBy(string $member, float $value):int
    {
        return $this->connection->zIncrBy($this->key, $value, $member);
    }

    public function zRange(float $fromValue, float $toValue):array
    {
        return $this->connection->zRange($this->key, $fromValue, $toValue, true);
    }

    public function getAll():array
    {
        return $this->zRange(0, -1);
    }
}