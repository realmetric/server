<?php


namespace App\Controllers;

use App\Models\DailySlicesModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class SlicesController extends AbstractController
{
    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $response = [];

        $todaySliceTotals = [];
        $yesterdaySliceTotals = [];

        $currTime = time();

        $totals = $this->mysql->dailySlices->getTotalsByMetricId($attributes['metric_id'], $currTime);

        $yesterdayTotals = [];
        try {
            $yesterdayTotals = $this->mysql->dailySlices
                ->setTable(DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
                ->getTotalsByMetricId($attributes['metric_id'], $currTime);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '42S02') { //table does not exists
                throw $exception;
            }
        }

        foreach ($totals as $total) {
            $todaySliceTotals[$total['slice_id']] = $total['value'];
        }

        foreach ($yesterdayTotals as $total) {
            $yesterdaySliceTotals[$total['slice_id']] = $total['value'];
        }

        $usedSliceIds = array_column($totals, 'slice_id');
        $allSlices = $this->mysql->slices->getAllBySliceIds($usedSliceIds, ['id', 'category', 'name']);
        foreach ($allSlices as $slice) {
            $data = [
                'id' => $slice['id'],
                'name' => $slice['name'],
                'total' => $todaySliceTotals[$slice['id']],
            ];
            if (!empty($yesterdaySliceTotals[$slice['id']]) && $todaySliceTotals[$slice['id']] > 0) {
                $diff = (($todaySliceTotals[$slice['id']] * 100) / $yesterdaySliceTotals[$slice['id']]) - 100;
                $data['diff'] = $diff;
            }

            $response[$slice['category']][] = $data;
        }


        return $this->jsonResponse([
            'slices' => $response
        ]);
    }
}