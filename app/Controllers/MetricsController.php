<?php


namespace App\Controllers;


use Psr\Http\Message\ServerRequestInterface;

class MetricsController extends AbstractController
{
    public function getAll()
    {
        $totals = $this->mysql->dailyMetrics->getAllMetrics();
        $metrics = $this->mysql->metrics->getAll();
        $metrics = array_column($metrics, 'name', 'id');
        foreach ($totals as &$record) {
            $record['name'] = $metrics[$record['metric_id']];
        }
        return $this->jsonResponse(['metrics' => $totals]);
    }

    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $totals = $this->mysql->dailyMetrics->getByMetricId($attributes['metric_id']);
//        $metrics = array_column($this->mysql->metrics->getByIds(array_column($totals, 'metric_id')), 'name', 'id');

        foreach ($totals as &$record) {
//            $record['name'] = $metrics[$record['metric__id']];
            $hour = floor($record['minute']/60);
            $minute = $record['minute'] - (60*$hour);
            unset($record['minute']);
            $record['datetime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d ') . $hour . ':' . $minute));
        }
        return $this->jsonResponse(['metrics' => $totals]);
    }
}