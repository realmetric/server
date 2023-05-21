<?php

namespace App\Library;

use App\Model\DailyMetricsModel;
use App\Model\DailyMetricTotalsModel;
use App\Model\DailySlicesModel;
use App\Model\DailySliceTotalsModel;
use App\Model\MetricsModel;
use App\Model\SlicesModel;

class EventSaver
{
    public function __construct(
        private readonly MetricsModel           $metrics,
        private readonly SlicesModel            $slices,
        private readonly DailyMetricsModel      $dailyMetrics,
        private readonly DailySlicesModel       $dailySlices,
        private readonly DailyMetricTotalsModel $dailyMetricTotals,
        private readonly DailySliceTotalsModel  $dailySliceTotals,
    )
    {
    }

    public function save(string $metric, int $value, int $timestamp, array $slices = []): void
    {
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);

        $metricId = $this->metrics->getId($metric);
        $this->dailyMetrics->setTableFromTimestamp($timestamp)->track($metricId, $value, $minute);
        $this->dailyMetricTotals->track($metricId, $value);

        foreach ($slices as $sliceGroup => $slice) {
            $sliceId = $this->slices->getId($sliceGroup, $slice);
            $this->dailySlices->setTableFromTimestamp($timestamp)->track($metricId, $sliceId, $value, $minute);
            $this->dailySliceTotals->track($metricId, $sliceId, $value);
        }
    }
}
