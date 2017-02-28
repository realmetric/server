<?php


namespace App\Controllers;

use App\Models\DailySlicesModel;
use Psr\Http\Message\ServerRequestInterface;

class SlicesController extends AbstractController
{
    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $totals = $this->mysql->dailySlices->getByMetricId($attributes['metric_id']);
        $yesterdayTotals = $this->mysql->dailySlices
            ->setTable(DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getByMetricId($attributes['metric_id']);
        $slices = array_column(
            $this->mysql->slices->getByIds(
                array_merge(
                    array_column($totals, 'slice_id'),
                    array_column($yesterdayTotals, 'slice_id'))
                ), 'name', 'id');

        return $this->jsonResponse([
            'values' => [
                date('Y-m-d') => $totals,
                date('Y-m-d', strtotime('-1 day')) => $yesterdayTotals,
            ],
            'slices' => $slices

        ]);
    }
}