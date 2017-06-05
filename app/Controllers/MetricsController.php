<?php


namespace App\Controllers;


use App\Commands\Raw\Metrics;
use App\Models\DailyMetricsModel;
use App\Models\MetricsModel;
use App\Values\Format;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController extends AbstractController
{
    public function getAll(ServerRequestInterface $request)
    {
        // Total values by day
        $todayTotals = $this->mysql->dailyMetrics->getTotals();
        $todayTotals = array_column($todayTotals, 'value', 'id');

        // Filter by slice
        $sliceId = (int)$request->getAttribute('slice_id', 0);
        if ($sliceId) {
            $metricsBySlice = $this->mysql->dailySlices->getMetricsBySlice($sliceId);
            $newTotals = [];
            foreach ($metricsBySlice as $metricId) {
                $newTotals[$metricId] = $todayTotals[$metricId];
            }
            $todayTotals = $newTotals;
        }

        // All metric names
        $metrics = $this->mysql->metrics->getAll();
        $metrics = array_column($metrics, 'name', 'id');

        $format = new Format();

        // Build Result
        $result = [];
        foreach ($todayTotals as $metricId => $value) {
            $metricName = $metrics[$metricId];
            $nameParts = explode('.', $metricName);
            $catName = count($nameParts) > 1 ? $nameParts[0] : 'Other';

            $result[$catName][] = [
                'id' => $metricId,
                'name' => $metricName,
                'diff' => 123,
                'total' => $format->shorten($value),
            ];
        }
        return $this->jsonResponse(['metrics' => $result]);
    }

    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();
        $data = $this->mysql->dailyMetrics->getByMetricId($attributes['metric_id']);
        $yesterdayData = $this->mysql->dailyMetrics
            ->setTable(DailyMetricsModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getByMetricId($attributes['metric_id']);

        $today = [];
        foreach ($data as $record) {
            $today[$record['minute']] = $record['value'];
        }
        $yesterday = [];
        foreach ($yesterdayData as $record) {
            $yesterday[$record['minute']] = $record['value'];
        }

        $values = [
            date('Y-m-d') => (object)$today,
            date('Y-m-d', strtotime('-1 day')) => (object)$yesterday,
        ];

        return $this->jsonResponse(['values' => $values]);
    }
}