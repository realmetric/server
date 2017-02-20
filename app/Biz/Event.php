<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save(string $metricName, float $value = 0, string $time, array $slices = [])
    {
        $metricId = $this->mysql->metrics->getId($metricName);
        $eventId = $this->mysql->dailyMetrics->create($metricId, $value, $time);

        $this->mysql->dailySlices->create($eventId, $metricId, 1, 1, $value, $time);
        $this->mysql->dailySlices->create($eventId, $metricId, 2, 1, $value, $time);
        $this->mysql->dailySlices->create($eventId, $metricId, 3, 2, $value, $time);
        $this->mysql->dailySlices->create($eventId, $metricId, 4, 2, $value, $time);
        $this->mysql->dailySlices->create($eventId, $metricId, 5, 3, $value, $time);
        $this->mysql->dailySlices->create($eventId, $metricId, 6, 2, $value, $time);
    }
}