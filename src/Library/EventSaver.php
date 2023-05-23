<?php

namespace App\Library;

use App\Model\DailyMetricsModel;
use App\Model\DailyMetricTotalsModel;
use App\Model\DailySlicesModel;
use App\Model\DailySliceTotalsModel;
use App\Model\MetricsModel;
use App\Model\MonthlyMetricsModel;
use App\Model\MonthlySlicesModel;
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
        private readonly MonthlyMetricsModel    $monthlyMetrics,
        private readonly MonthlySlicesModel     $monthlySlices,
    )
    {
    }

    public function save(string $metric, int $value, int $timestamp, array $slices = []): void
    {
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
        $dateTime = (new \DateTime)->setTimestamp($timestamp);

        $metricId = $this->metrics->getId($metric);
        if ($timestamp > time() - 3600 * 24 * 3) {
            $this->dailyMetrics->setTableFromTimestamp($timestamp)->track($metricId, $value, $minute);
            $this->dailyMetricTotals->track($metricId, $value);
        }
        $this->monthlyMetrics->track($metricId, $value, $dateTime);

        foreach ($slices as $sliceGroup => $slice) {
            $sliceId = $this->slices->getId($sliceGroup, $slice);
            if ($timestamp > time() - 3600 * 24 * 3) {
                $this->dailySlices->setTableFromTimestamp($timestamp)->track($metricId, $sliceId, $value, $minute);
                $this->dailySliceTotals->track($metricId, $sliceId, $value);
            }
            $this->monthlySlices->track($metricId, $sliceId, $value, $dateTime);
        }
    }
}
