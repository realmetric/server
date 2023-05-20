<?php

namespace App\Controller;

use App\Model\DailyMetricsModel;
use App\Model\DailySlicesModel;
use App\Model\MonthlyMetricsModel;
use App\Model\MonthlySlicesModel;
use Illuminate\Database\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ValuesController extends AbstractController
{
    public function __construct(
        private readonly DailySlicesModel    $dailySlices,
        private readonly DailyMetricsModel   $dailyMetrics,
        private readonly MonthlySlicesModel  $monthlySlices,
        private readonly MonthlyMetricsModel $monthlyMetrics,
    )
    {
    }

    #[Route('/values/minutes', methods: ['GET'])]
    public function minutes(Request $request)
    {
        $metricId = $request->query->get('metric_id');
        $sliceId = $request->query->get('slice_id');
        $from = $request->query->has('from') ? new \DateTime($request->query->get('from')) : new \DateTime('today');
        $to = $request->query->has('to') ? new \DateTime($request->query->get('to')) : new \DateTime('tomorrow');
        $prevFrom = $request->query->has('prev_from') ? new \DateTime($request->query->get('prev_from')) : new \DateTime('yesterday');
        $prevTo = $request->query->has('prev_to') ? new \DateTime($request->query->get('prev_to')) : new \DateTime('today');

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

        return $this->json(['values' => $values]);
    }

    #[Route('/values/days', methods: ['GET'])]
    public function days(Request $request)
    {
        $metricId = $request->query->get('metric_id');
        $sliceId = $request->query->get('slice_id');
        $from = $request->query->has('from') ? new \DateTime($request->query->get('from')) : new \DateTime('first day of this month midnight');
        $to = $request->query->has('to') ? new \DateTime($request->query->get('to')) : new \DateTime('first day of next month midnight');
        $prevFrom = $request->query->has('prev_from') ? new \DateTime($request->query->get('prev_from')) : new \DateTime('first day of this month midnight');
        $prevTo = $request->query->has('prev_to') ? new \DateTime($request->query->get('prev_to')) : new \DateTime('first day of next month midnight');

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

        return $this->json(['values' => $values]);
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
                $data = $this->dailyMetrics
                    ->setTableFromTimestamp($dt->getTimestamp(), false)
                    ->getByMetricId($metricId);
                if ($data) {
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
        int       $metricId,
        \DateTime $from = null,
        \DateTime $to = null
    ): array
    {
        $data = $this->monthlyMetrics
            ->getByMetricId($metricId, $from, $to);

        $result = [];
        if ($data) {
            $result = array_column($data, 'value', 'date');
        }

        $result = $this->formatDaysResult($result, $from, $to);

        return $result;
    }

    private function getSliceValuesByMinutes(
        int       $metricId,
        int       $sliceId,
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
                $data = $this->dailySlices
                    ->setTable(DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d'))
                    ->getValues($metricId, $sliceId);
                if ($data) {
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
        int       $metricId,
        int       $sliceId,
        \DateTime $from = null,
        \DateTime $to = null
    ): array
    {
        $result = [];
        $data = $this->monthlySlices
            ->getValues($metricId, $sliceId, $from, $to);
        if ($data) {
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
            if (!isset($result[$dt->format('Y-m-d')])) {
                $result[$dt->format('Y-m-d')] = null;
            }
        }
        return $result;
    }
}
