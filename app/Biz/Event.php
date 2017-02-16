<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save($metric, $value = 0, $slices = [], $time = 'now')
    {
        $metricId = 1;

        $eventId = $this->mysql->day->create($metricId, $value, $time);

    }
}