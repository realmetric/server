<?php

namespace App\Controllers;

use App\Models\DailySlicesModel;
use Psr\Http\Message\ServerRequestInterface;

class ValuesDayController extends AbstractController
{
    public function get(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;
        $sliceId = isset($queryParams['slice_id']) ? (int)$queryParams['slice_id'] : null;

        if (!$metricId || !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }

        $data = $this->mysql->dailySlices->getValues($metricId, $sliceId);
        $yesterdayData = $this->mysql->dailySlices
            ->setTable(DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getValues($metricId, $sliceId);

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
            date('Y-m-d', strtotime('-1 day')) => $yesterday,
        ];

        return $this->jsonResponse(['values' => $values]);
    }
}