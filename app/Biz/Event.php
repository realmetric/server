<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save(string $metricName, float $value = 1.0, string $time, array $slices = [])
    {
        $metricId = $this->mysql->metrics->getId($metricName);
        $eventId = $this->mysql->dailyMetrics->create($metricId, $value, $time);

        foreach ($slices as $category => $sliceName) {
            $sliceId = $this->mysql->slices->getId($category, $sliceName);
            $this->mysql->dailySlices->create($metricId, $sliceId, $value, $time);
        }

    }
}