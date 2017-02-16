<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save($metric, $value, $slices, $time)
    {
        $this->mysql->day->setTable('day_' . date('Y-m-d'))
            ->create();

    }
}