<?php
declare(strict_types = 1);

namespace App\Events;

use App\Injectable;

class Pack
{
    use Injectable;

    public function addSlice($metricId, $categoryId, $sliceId, int $minute, int $value)
    {
        $member = json_encode([$metricId, $categoryId, $sliceId]);
        $this->redis->track_aggr_slices->zIncrBy($member, $value);
    }

    public function addMetric($metricId, int $minute, int $value)
    {
        $member = json_encode([$metricId]);
        $this->redis->track_aggr_metrics->zIncrBy($member, $value);
    }

    public function flushMetrics()
    {
        $metrics = $this->redis->track_aggr_metrics->getAll();
        $this->redis->track_aggr_metrics->del();
        $records = [];
        foreach ($metrics as $member => $value) {
            $memberData = json_decode($member, true);
            $metricName = $memberData[0];
//            $minute = $memberData[1];
            $value = (int)$value;

            $minute = (int)(date('H') * 60 + date('i'));
            // Find metric
            $metricId = $this->mysql->metrics->getId($metricName);

            $records[] = ['metric_id' => $metricId, 'value' => $value, 'minute' => $minute];

        }
        if (!count($records)) {
            return 0;
        }
        $this->mysql->dailyMetrics->insertBatch($records);
        return count($records);
    }

    public function flushSlices()
    {
        $slices = $this->redis->track_aggr_slices->getAll();
        $this->redis->track_aggr_slices->del();
        $records = [];
        foreach ($slices as $member => $value) {
            $memberData = json_decode($member, true);
            $metricName = $memberData[0];
            $category = $memberData[1];
            $sliceName = $memberData[2];
//            $minute = $memberData[3];
            $value = (int)$value;

            $category = (string)$category;
            $sliceName = (string)$sliceName;

            $minute = (int)(date('H') * 60 + date('i'));
            // Find metrics and slices
            $metricId = $this->mysql->metrics->getId($metricName);
            $sliceId = $this->mysql->slices->getId($category, $sliceName);

            $records[] = ['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value, 'minute' => $minute];
            //$this->mysql->dailySliceTotals->addValue($metricId, $sliceId, $value);
        }
        if (!count($records)) {
            return 0;
        }
        $this->mysql->dailySlices->insertBatch($records);

        return count($slices);
    }
}