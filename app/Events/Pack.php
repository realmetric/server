<?php
declare(strict_types = 1);

namespace App\Events;

use App\Injectable;

class Pack
{
    use Injectable;

    public function addSlice($metricId, $categoryId, $sliceId, string $time, int $value)
    {
        $member = json_encode([$metricId, $categoryId, $sliceId, $time]);
        $this->redis->track_aggr_slices->zIncrBy($member, $value);
    }

    public function addMetric($metricId, string $time, int $value)
    {
        $member = json_encode([$metricId, $time]);
        $this->redis->track_aggr_metrics->zIncrBy($member, $value);
    }

    public function flushMetrics()
    {
        $metrics = $this->redis->track_aggr_metrics->getAll();
        $this->redis->track_aggr_metrics->del();
        $saved = 0;
        foreach ($metrics as $member => $value) {
            $memberData = json_decode($member, true);
            $metricName = $memberData[0];
            $date = $memberData[1];
            $value = (int)$value;

            // Find metric
            $metricId = $this->mysql->metrics->getId($metricName);

            $id = $this->mysql->dailyMetrics->createOrIncrement($metricId, $value, $date);

            if ($id) {
                $saved++;
            }
        }
        return [count($metrics), $saved];
    }

    public function flushSlices()
    {
        $slices = $this->redis->track_aggr_slices->getAll();
        $this->redis->track_aggr_slices->del();
        $saved = 0;
        foreach ($slices as $member => $value) {
            $memberData = json_decode($member, true);
            $metricName = $memberData[0];
            $category = $memberData[1];
            $sliceName = $memberData[2];
            $date = $memberData[3];
            $value = (int)$value;

            $category = (string)$category;
            $sliceName = (string)$sliceName;

            // Find metrics and slices
            $metricId = $this->mysql->metrics->getId($metricName);
            $sliceId = $this->mysql->slices->getId($category, $sliceName);

            $id = $this->mysql->dailySlices->createOrIncrement($metricId, $sliceId, $value, $date);
            $this->mysql->dailySliceTotals->addValue($metricId, $sliceId, $value);

            if ($id) {
                $saved++;
            }
        }

        return [count($slices), $saved];
    }
}