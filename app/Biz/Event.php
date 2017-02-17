<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save(string $metricName, float $value = 0, string $time, array $slices = [])
    {
        $metricId = $this->mysql->metrics->getId($metricName);
        $eventId = $this->mysql->dailyMetrics->create($metricId, $value, $time);

        $this->mysql->dailySlices->create($eventId, $metricId, 12, 3);
        $this->mysql->dailySlices->create($eventId, $metricId, 14, 6);

    }
}