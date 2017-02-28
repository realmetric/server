<?php


namespace App\Controllers;


use App\Commands\Raw\Metrics;
use App\Models\DailyMetricsModel;
use App\Models\MetricsModel;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController extends AbstractController
{
    public function getAll()
    {
        $todayTotals = $this->mysql->dailyMetrics->getAllMetrics();
        $yesterdayTotals = $this->mysql->dailyMetrics
            ->setTable(DailyMetricsModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getAllMetrics();
        $metrics = $this->mysql->metrics->getAll();

        $metrics = array_column($metrics, 'name', 'id');
        foreach ($todayTotals as &$record) {
            $record['name'] = $metrics[$record['metric_id']];
        }
        foreach ($yesterdayTotals as &$record) {
            $record['name'] = $metrics[$record['metric_id']];
        }

        return $this->jsonResponse([
            'metrics' => [
                date('Y-m-d') => $todayTotals,
                date('Y-m-d', strtotime('-1 day')) => $yesterdayTotals
            ],
        ]);
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
            date('Y-m-d') => $today,
            date('Y-m-d', date('Y_m_d', strtotime('-1 day'))) => $yesterday,
        ];

        return $this->jsonResponse(['values' => $values]);
    }
}