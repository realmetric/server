<?php

namespace App\Controller;

use App\Library\EventSaver;
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
        private readonly EventSaver          $eventSaver,
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
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'minutely values for days']);

        $metricId = $request->query->get('metric_id');
        $sliceId = $request->query->get('slice_id');

        if (!$metricId && !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }

        $values = [];
        if ($sliceId) {
            $todayValues = $this->dailySlices->getTodayValues($metricId, $sliceId);
            $yesterdayValues = $this->dailySlices->getYesterdayValues($metricId, $sliceId);
        } else {
            $todayValues = $this->dailyMetrics->getTodayValues($metricId);
            $yesterdayValues = $this->dailyMetrics->getYesterdayValues($metricId);
        }
        $values['curr'][date('Y-m-d')] = array_column($todayValues, 'value', 'minute');
        $values['prev'][date('Y-m-d', strtotime('yesterday'))] = array_column($yesterdayValues, 'value', 'minute');

        return $this->json(['values' => $values]);
    }

    #[Route('/values/days', methods: ['GET'])]
    public function days(Request $request)
    {
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'daily values for weeks']);

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
