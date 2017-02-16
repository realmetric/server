<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save($metric, $value = 0, $slices = [], $time = 'now')
    {
        $metricId = 1;

        $eventId = $this->mysql->dailyMetrics->create($metricId, $value, $time);

        $this->mysql->dailySlices->create($eventId, $metricId, 12, 3);
        $this->mysql->dailySlices->create($eventId, $metricId, 14, 6);

    }
}