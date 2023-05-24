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
    private array $batchValues = [];

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

    public function save(string $metric, int $value, int $timestamp, array $slices, $batch = true): void
    {
        if (!$batch) {
            $this->directSave($metric, $value, $timestamp, $slices);
        } else {
            $this->batchSave($metric, $value, $timestamp, $slices);
        }
    }

    private function directSave(string $metric, int $value, int $timestamp, array $slices): void
    {
        $this->track($metric, $value, $timestamp);
        foreach ($slices as $sliceGroup => $slice) {
            $this->track($metric, $value, $timestamp, $sliceGroup, $slice);
        }
    }

    private function batchSave(string $metric, int $value, int $timestamp, array $slices): void
    {
        $key = json_encode([$metric, $timestamp, null, null]);
        @$this->batchValues[$key] += $value;
        foreach ($slices as $sliceGroup => $slice) {
            $key = json_encode([$metric, $timestamp, $sliceGroup, $slice]);
            @$this->batchValues[$key] += $value;
        }
        if (!mt_rand(0, 99)) {
            $this->flush();
        }
    }

    private function flush(): void
    {
        $batchData = $this->batchValues;
        $this->batchValues = [];
        foreach ($batchData as $key => $value) {
            [$metric, $timestamp, $sliceGroup, $slice] = json_decode($key, true);
            $this->track($metric, $value, $timestamp, $sliceGroup, $slice);
        }
    }

    private function track(string $metric, int $value, int $timestamp, ?string $sliceGroup = null, ?string $slice = null): void
    {
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
        $dateTime = (new \DateTime)->setTimestamp($timestamp);

        $metricId = $this->metrics->getId($metric);
        if ($timestamp > time() - 3600 * 24 * 3) {
            $this->dailyMetrics->setTableFromTimestamp($timestamp)->track($metricId, $value, $minute);
            $this->dailyMetricTotals->track($metricId, $value);
        }
        $this->monthlyMetrics->track($metricId, $value, $dateTime);

        if ($sliceGroup == null && $slice == null) {
            return;
        }

        $sliceId = $this->slices->getId($sliceGroup, $slice);
        if ($timestamp > time() - 3600 * 24 * 3) {
            $this->dailySlices->setTableFromTimestamp($timestamp)->track($metricId, $sliceId, $value, $minute);
            $this->dailySliceTotals->track($metricId, $sliceId, $value);
        }
        $this->monthlySlices->track($metricId, $sliceId, $value, $dateTime);
    }
}
