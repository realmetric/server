<?php

namespace App\Library;

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
        $this->mysql->dailyRawSlices->insertBatchRaw(['metric_id', 'slice_id', 'value', 'minute'], $insertData);

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

    public function saveBatch($events, $metrics, $categories, $names, $timestamp)
    {
        $this->timer->startPoint('events prepare');
        $metricsResult = [];
        $slicesResult = [];

        foreach ($metrics as $id => $name) {
            $metrics[$id] = $this->mysql->metrics->getId($name);
        }

        $slices = [];
        foreach ($events as $event) {
            $value = (float)$event['v'] ?? 1;

            // Time
            if (isset($event['t'])) {
                $ts = is_numeric($event['t']) ? (int)$event['t'] : strtotime($event['t']);
            } else {
                $ts = time();
            }
            $minute = date('H', $ts) * 60 + date('i', $ts);


            // Metric
            $metricId = $metrics[$event['m']];
            array_push($metricsResult, $metricId, $value, $minute);

            // Slices
            if (!isset($event['s']) || !count($event['s'])) {
                continue;
            }
            foreach ($event['s'] as $slice) {
                $key = $slice[0] . '_' . $slice[1];
                if (!isset($slices[$key])) {
                    $category = $categories[$slice[0]];
                    $sliceName = $names[$slice[1]];
                    if ($category === null || $sliceName === null) {
                        continue;
                    }
                    $slices[$key] = $this->mysql->slices->getId($category, $sliceName);
                }
                array_push($slicesResult, $metricId, $slices[$key], $value, $minute);
            }
        }

        $this->timer->endPoint('events prepare');

        $this->timer->startPoint('events saving');
        $this->mysql->dailyRawMetrics
            ->setTableFromTimestamp($timestamp)
            ->insertBatchRaw(['metric_id', 'value', 'minute'], $metricsResult);
        $this->mysql->dailyRawSlices
            ->setTableFromTimestamp($timestamp)
            ->insertBatchRaw(['metric_id', 'slice_id', 'value', 'minute'], $slicesResult);
        $this->timer->endPoint('events saving');

        return count($events);
    }
}