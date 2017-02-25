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
        $data = $this->mysql->dailyMetrics->getByMetricId($attributes['metric_id']);

        $values = [];
        foreach ($data as $record) {
            $values[$record['minute']] = $record['value'];
        }

        return $this->jsonResponse(['values' => $values]);
    }
}