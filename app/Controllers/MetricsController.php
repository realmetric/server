<?php


namespace App\Controllers;


class MetricsController extends AbstractController
{
    public function getAll()
    {
        $metrics = $this->mysql->dailyMetrics->getAllMetrics();
        return $this->jsonResponse($metrics);
    }
}