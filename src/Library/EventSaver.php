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

    public function __destruct()
    {
        $this->flush();
    }

    public function save(string $metric, int $value, int $timestamp, array $slices, $batch = false): void
    {
        $this->batchSave($metric, $value, $timestamp, $slices);
        if (!$batch) {
            $this->flush();
        }
    }

    private function batchSave(string $metric, int $value, int $timestamp, array $slices): void
    {
        $date = date('Y-m-d', $timestamp);
        $minute = (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
        $keyDaily = json_encode([$metric, $minute, null, null]);
        $keyMonthly = json_encode([$metric, $date, null, null]);
        @$this->batchMonthlyValues[$keyMonthly] += $value;
        if ($timestamp > time() - 3600 * 24 * 2) {
            @$this->batchDailyValues[$keyDaily] += $value;
        }
        foreach ($slices as $sliceGroup => $slice) {
            $keyDaily = json_encode([$metric, $minute, $sliceGroup, $slice]);
            $keyMonthly = json_encode([$metric, $date, $sliceGroup, $slice]);
            @$this->batchMonthlyValues[$keyMonthly] += $value;
            if ($timestamp > time() - 3600 * 24 * 2) {
                @$this->batchDailyValues[$keyDaily] += $value;
            }
        }
        if (time() - $this->batchStarted >= 60) {
            $this->flush();
        }
    }

    private function flush(): void
    {
        $timeStart = microtime(true);
        $this->flushDaily($this->batchDailyValues);
        $this->batchDailyValues = [];
        $this->flushMonthly($this->batchMonthlyValues);
        $this->batchMonthlyValues = [];
        $this->batchStarted = time();
        echo "Flush done in " . round((microtime(true) - $timeStart) * 1000) . " ms. \n";
    }

    private function flushDaily(array $batchDailyData)
    {
        $dailyMetricsRows = [];
        $dailySlicesRows = [];
        $dailyMetricTotalsRows = [];
        $dailySliceTotalsRows = [];

        foreach ($batchDailyData as $key => $value) {
            [$metric, $minute, $sliceGroup, $slice] = json_decode($key, true);
            $metricId = $this->metrics->getId($metric);
            $dailyMetricsRows[] = ['metric_id' => $metricId, 'value' => $value, 'minute' => $minute];
            $dailyMetricTotalsRows[] = ['metric_id' => $metricId, 'value' => $value];
            if ($sliceGroup !== null || $slice !== null) {
                $sliceId = $this->slices->getId($sliceGroup, $slice);
                $dailySlicesRows[] = ['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value, 'minute' => $minute];
                $dailySliceTotalsRows[] = ['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value];
            }
        }
        $this->dailyMetrics->insertOrIncrementBatch($dailyMetricsRows);
        $this->dailyMetricTotals->insertOrIncrementBatch($dailyMetricTotalsRows);
        if (!empty($dailySlicesRows)) {
            $this->dailySlices->insertOrIncrementBatch($dailySlicesRows);
            $this->dailySliceTotals->insertOrIncrementBatch($dailySliceTotalsRows);
        }
    }

    private function flushMonthly(array $batchMonthlyData)
    {
        $monthlyMetricsRows = [];
        $monthlySlicesRows = [];
        foreach ($batchMonthlyData as $key => $value) {
            [$metric, $date, $sliceGroup, $slice] = json_decode($key, true);
            $metricId = $this->metrics->getId($metric);
            $monthlyMetricsRows[] = ['metric_id' => $metricId, 'value' => $value, 'date' => $date];
            if ($sliceGroup !== null || $slice !== null) {
                $sliceId = $this->slices->getId($sliceGroup, $slice);
                $monthlySlicesRows[] = ['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value, 'date' => $date];
            }
        }
        $this->monthlyMetrics->insertOrIncrementBatch($monthlyMetricsRows);
        if (!empty($monthlySlicesRows)) {
            $this->monthlySlices->insertOrIncrementBatch($monthlySlicesRows);
        }
    }
}
