<?php

namespace App\Controllers;

use App\Models\DailyMetricsModel;
use App\Models\DailySlicesModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class ValuesController extends AbstractController
{
    public function minutes(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;
        $sliceId = isset($queryParams['slice_id']) ? (int)$queryParams['slice_id'] : null;
        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('today');
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime('tomorrow');
        $prevFrom = isset($queryParams['prev_from']) ? new \DateTime($queryParams['prev_from']) : new \DateTime('yesterday');
        $prevTo = isset($queryParams['prev_to']) ? new \DateTime($queryParams['prev_to']) : new \DateTime('today');

        if (!$metricId && !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }

        $values = [];
        if ($sliceId) {
            $values['curr'] = $this->getSliceValuesByMinutes($metricId, $sliceId, $from, $to);
            $values['prev'] = $this->getSliceValuesByMinutes($metricId, $sliceId, $prevFrom, $prevTo);
        } else {
            $values['curr'] = $this->getMetricValuesByMinutes($metricId, $from, $to);
            $values['prev'] = $this->getMetricValuesByMinutes($metricId, $prevFrom, $prevTo);
        }

        return $this->jsonResponse(['values' => $values]);
    }

    public function days(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;
        $sliceId = isset($queryParams['slice_id']) ? (int)$queryParams['slice_id'] : null;
        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('first day of this month midnight');
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime('first day of next month midnight');
        $prevFrom = isset($queryParams['prev_from']) ? new \DateTime($queryParams['prev_from']) : new \DateTime('first day of this month midnight');
        $prevTo = isset($queryParams['prev_to']) ? new \DateTime($queryParams['prev_to']) : new \DateTime('first day of next month midnight');

        if (!$metricId && !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }
        $values = [];
        if ($sliceId) {
            $values['curr'] = $this->getSliceValuesByDays($metricId, $sliceId, $from, $to);
            $values['prev'] = $this->getSliceValuesByDays($metricId, $sliceId, $prevFrom, $prevTo);
        } else {
            $values['curr'] = $this->getMetricValuesByDays($metricId, $from, $to);
            $values['prev'] = $this->getMetricValuesByDays($metricId, $prevFrom, $prevTo);
        }

        return $this->jsonResponse(['values' => $values]);
    }

    private function getMetricValuesByMinutes(int $metricId, \DateTime $from, \DateTime $to): array
    {
        $result = [];

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($from, $interval, $to);
        foreach ($period as $dt) {
            /**
             * @var \DateTime $dt
             */
            try {
                $data = $this->mysql->dailyMetrics
                    ->setTableFromTimestamp($dt->getTimestamp(), false)
                    ->getByMetricId($metricId);
                if ($data){
                    $result[$dt->format('Y-m-d')] = array_column($data, 'value', 'minute');
                } else {
                    $result[$dt->format('Y-m-d')] = null;
                }
            } catch (QueryException $exception) {
                if ($exception->getCode() === '42S02') { //table does not exists
                    $result[$dt->format('Y-m-d')] = null;
                } else {
                    throw $exception;
                }
            }
        }

        return $result;
    }

    private function getMetricValuesByDays(
        int $metricId,
        \DateTime $from = null,
        \DateTime $to = null
    ): array
    {
        $data = $this->mysql->monthlyMetrics
            ->getByMetricId($metricId, $from, $to);

        $result = [];
        if ($data){
            $result = array_column($data, 'value', 'date');
        }

        $result = $this->formatDaysResult($result, $from, $to);

        return $result;
    }

    private function getSliceValuesByMinutes(
        int $metricId,
        int $sliceId,
        \DateTime $from,
        \DateTime $to
    ): array
    {
        $result = [];

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($from, $interval, $to);

        foreach ($period as $dt) {
            /**
             * @var \DateTime $dt
             */
            try {
                $data = $this->mysql->dailySlices
                    ->setTable(DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d'))
                    ->getValues($metricId, $sliceId);
                if ($data){
                    $result[$dt->format('Y-m-d')] = array_column($data, 'value', 'minute');
                } else {
                    $result[$dt->format('Y-m-d')] = null;
                }
            } catch (QueryException $exception) {
                if ($exception->getCode() === '42S02') { //table does not exists
                    $result[$dt->format('Y-m-d')] = null;
                } else {
                    throw $exception;
                }
            }
        }

        return $result;
    }

    private function getSliceValuesByDays(
        int $metricId,
        int $sliceId,
        \DateTime $from = null,
        \DateTime $to = null
    ): array
    {
        $result = [];
        $data = $this->mysql->monthlySlices
            ->getValues($metricId, $sliceId, $from, $to);
        if ($data){
            $result = array_column($data, 'value', 'date');
        }

        $result = $this->formatDaysResult($result, $from, $to);

        return $result;
    }

    private function formatDaysResult(array $result, \DateTime $from = null, \DateTime $to = null)
    {
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($from, $interval, $to);
        foreach ($period as $dt) {
            if (!isset($result[$dt->format('Y-m-d')])){
                $result[$dt->format('Y-m-d')] = null;
            }
        }
        return $result;
    }
}