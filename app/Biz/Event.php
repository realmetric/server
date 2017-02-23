<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save(string $metricName, float $value = 1.0, $time, array $slices = [])
    {
        if (is_numeric($time)){
            $time = date('Y-m-d H:i:s', $time);
        }
        $metricId = $this->mysql->metrics->getId($metricName);
        $eventId = $this->mysql->dailyRawMetrics->create($metricId, $value, $time);

        foreach ($slices as $category => $sliceName) {
            $sliceId = $this->mysql->slices->getId($category, $sliceName);
            $this->mysql->dailyRawSlices->create($metricId, $sliceId, $value, $time);
        }

    }
}