<?php

namespace App\Biz;

class Event
{
    use \App\Injectable;

    public function save(string $metricName, float $value = 1.0, $time, array $slices = [])
    {
        if (is_numeric($time)) {
            $time = date('Y-m-d H:i:s', $time);
        }
        $metricId = $this->mysql->metrics->getId($metricName);
        $eventId = $this->mysql->dailyRawMetrics->create($metricId, $value, $time);

        $ts = strtotime($time);
        $minute = date('H', $ts) * 60 + date('i', $ts);

        if (!count($slices)) {
            return $eventId;
        }

        // --------------- Saving slices ---------------------

        $insertData = [];
        foreach ($slices as $category => $sliceName) {
            $sliceId = $this->mysql->slices->getId($category, $sliceName);
            $insertData[] = [
                'metric_id' => $metricId,
                'slice_id' => $sliceId,
                'value' => $value,
                'minute' => $minute,
            ];
        }
        $this->mysql->dailyRawSlices->insertBatch($insertData);

        return $eventId;
    }
}