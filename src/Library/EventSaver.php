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
    private array $batchDailyValues = [];
    private array $batchMonthlyValues = [];
    private int $batchStarted;

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
        $this->batchStarted = time();
    }

    public function save(string $metric, int $value, int $timestamp, array $slices, $batch = false): void
    {
        if (!$batch) {
            $this->directSave($metric, $value, $timestamp, $slices);
        } else {
            $this->batchSave($metric, $value, $timestamp, $slices);
        }
    }

    private function directSave(string $metric, int $value, int $timestamp, array $slices): void
    {
        $date = date('Y-m-d', $timestamp);
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
        $this->trackMonthly($metric, $value, $date);
        if ($timestamp > time() - 3600 * 24 * 3) {
            $this->trackDaily($metric, $value, $date, $minute);
        }
        foreach ($slices as $sliceGroup => $slice) {
            $this->trackMonthly($metric, $value, $date, $sliceGroup, $slice);
            if ($timestamp > time() - 3600 * 24 * 3) {
                $this->trackDaily($metric, $value, $date, $minute, $sliceGroup, $slice);
            }
        }
    }

    private function batchSave(string $metric, int $value, int $timestamp, array $slices): void
    {
        $date = date('Y-m-d', $timestamp);
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
        $keyDaily = json_encode([$metric, $date, $minute, null, null]);
        $keyMonthly = json_encode([$metric, $date, null, null]);
        @$this->batchMonthlyValues[$keyMonthly] += $value;
        if ($timestamp > time() - 3600 * 24 * 3) {
            @$this->batchDailyValues[$keyDaily] += $value;
        }
        foreach ($slices as $sliceGroup => $slice) {
            $keyDaily = json_encode([$metric, $date, $minute, $sliceGroup, $slice]);
            $keyMonthly = json_encode([$metric, $date, $sliceGroup, $slice]);
            @$this->batchDailyValues[$keyDaily] += $value;
            @$this->batchMonthlyValues[$keyMonthly] += $value;
        }
        if (time() - $this->batchStarted >= 60) {
            $this->flush();
        }
    }

    private function flush(): void
    {
        $timeStart = microtime(true);
        // Daily
        $batchDailyData = $this->batchDailyValues;
        $this->batchDailyValues = [];
        foreach ($batchDailyData as $key => $value) {
            [$metric, $date, $minute, $sliceGroup, $slice] = json_decode($key, true);
            $this->trackDaily($metric, $value, $date, $minute, $sliceGroup, $slice);
        }
        // Monthly
        $batchMonthlyData = $this->batchMonthlyValues;
        $this->batchMonthlyValues = [];
        foreach ($batchMonthlyData as $key => $value) {
            [$metric, $date, $sliceGroup, $slice] = json_decode($key, true);
            $this->trackMonthly($metric, $value, $date, $sliceGroup, $slice);
        }
        echo "Flush done in " . round((microtime(true) - $timeStart) * 1000) . " seconds. \n";
        $this->batchStarted = time();
    }

    private function trackMonthly(string $metric, int $value, string $date, ?string $sliceGroup = null, ?string $slice = null): void
    {
        if (!str_contains($date, '-')) {
            throw new \Exception('Wrong date format: ' . $date);
        }
        $metricId = $this->metrics->getId($metric);
        $this->monthlyMetrics->track($metricId, $value, $date);
        if ($sliceGroup == null && $slice == null) {
            return;
        }
        $sliceId = $this->slices->getId($sliceGroup, $slice);
        $this->monthlySlices->track($metricId, $sliceId, $value, $date);
    }

    private function trackDaily(string $metric, int $value, string $date, int $minute, ?string $sliceGroup = null, ?string $slice = null): void
    {
        if (!str_contains($date, '-')) {
            throw new \Exception('Wrong date format: ' . $date);
        }
        if ($minute > 24 * 60) {
            throw new \Exception('Wrong minute format: ' . $minute);
        }
        $timestamp = strtotime($date);

        $metricId = $this->metrics->getId($metric);
        $this->dailyMetrics->setTableFromTimestamp($timestamp)->track($metricId, $value, $minute);
        $this->dailyMetricTotals->track($metricId, $value);

        if ($sliceGroup == null && $slice == null) {
            return;
        }

        $sliceId = $this->slices->getId($sliceGroup, $slice);
        $this->dailySlices->setTableFromTimestamp($timestamp)->track($metricId, $sliceId, $value, $minute);
        $this->dailySliceTotals->track($metricId, $sliceId, $value);
    }
}
