<?php

namespace App\Controllers;

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

        $values = [];
        foreach ($data as $record) {
            $values[$record['minute']] = $record['value'];
        }

        return $this->jsonResponse(['values' => $values]);
    }
}