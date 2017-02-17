<?php


namespace App\Controllers;


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
        return $this->jsonResponse($totals);
    }
}