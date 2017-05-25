<?php


namespace App\Controllers;


use App\Commands\Raw\Metrics;
use App\Models\DailyMetricsModel;
use App\Models\MetricsModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController extends AbstractController
{
    public function getAll()
    {
        $todayTotals = $this->mysql->dailyMetrics->getAllMetrics();
//        $yesterdayTotals = $this->mysql->dailyMetrics
//            ->setTable(DailyMetricsModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
//            ->getAllMetrics();

        $metrics = $this->mysql->metrics->getAll();
        $metrics = array_column($metrics, 'name', 'id');

        $result = [];
        foreach ($todayTotals as $total) {
            $metricName = $metrics[$total['metric_id']];
            $nameParts = explode('.', $metricName);
            $catName = count($nameParts) > 1 ? strtolower($nameParts[0]) : 'other';

            $result[$catName][] = [
                'id' => $metrics[$total['metric_id']],
                'name' => $metricName,
                'diff' => 123,
                'total' => $total['value'],
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