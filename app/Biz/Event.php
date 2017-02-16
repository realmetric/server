<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save($metric, $value = 0, $slices = [], $time = 'now')
    {
        $metricId = 1;

        $eventId = $this->mysql->dailyMetrics->create($metricId, $value, $time);

    }
}