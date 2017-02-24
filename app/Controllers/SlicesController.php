<?php


namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;

class SlicesController extends AbstractController
{
    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $totals = $this->mysql->dailySlices->getTotalsByMetricId($attributes['metric_id']);
        $slices = array_column($this->mysql->slices->getByIds(array_column($totals, 'slice_id')), 'name', 'id');

        foreach ($totals as &$record) {
            $record['name'] = $slices[$record['slice_id']];
            $hour = floor($record['minute']/60);
            $minute = $record['minute'] - (60*$hour);
            unset($record['minute']);
            $record['datetime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d ') . $hour . ':' . $minute));
        }
        return $this->jsonResponse(['slices' => $totals]);
    }
}