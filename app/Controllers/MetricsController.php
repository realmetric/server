<?php


namespace App\Controllers;


class MetricsController extends AbstractController
{
    public function getAll()
    {
//        $this->mysql->dailyMetrics->create();
        return $this->jsonResponse();
    }
}