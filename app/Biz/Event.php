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
        $insertData = $this->getSlicesInsertData($slices, $metricId, $value, $minute);
        $this->mysql->dailyRawSlices->insertBatch($insertData);

        return $eventId;
    }

    private function getSlicesInsertData($slices, $metricId, $value, $minute)
    {
        $insertData = [];
        foreach ($slices as $category => $sliceName) {
            if ($sliceName === null) {
                continue;
            }
            $sliceId = $this->mysql->slices->getId($category, $sliceName);
            array_push($insertData, $metricId, $sliceId, $value, $minute);
        }
        return $insertData;
    }

    public function saveBatch($events)
    {
        $metrics = [];
        $slices = [];

        $this->timer->startPoint('events prepare');
        foreach ($events as $event) {
            $metricName = $event['metric'];
            $value = (float)$event['value'] ?? 1;

            // Time
            if (isset($event['time'])) {
                $ts = is_numeric($event['time']) ? (int)$event['time'] : strtotime($event['time']);
            } else {
                $ts = time();
            }
            $minute = date('H', $ts) * 60 + date('i', $ts);


            // Metric
            $metricId = $this->mysql->metrics->getId($metricName);
            array_push($metrics, $metricId, $value, $minute);

            // Slices
            if (!isset($event['slices']) || !count($event['slices'])) {
                continue;
            }
            $slicesData = $this->getSlicesInsertData($event['slices'], $metricId, $value, $minute);
            foreach ($slicesData as $data) {
                $slices[] = $data;
            }
        }
        $this->timer->endPoint('events prepare');

        $this->timer->startPoint('events saving');
        $this->mysql->dailyRawMetrics->insertBatch(['metric_id', 'value', 'minute'], $metrics);
        $this->mysql->dailyRawSlices->insertBatch(['metric_id', 'slice_id', 'value', 'minute'], $slices);
        $this->timer->endPoint('events saving');

        return count($metrics);
    }
}