<?php


namespace App\Controllers;

use App\Models\DailySlicesModel;
use App\Values\Format;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class SlicesController extends AbstractController
{
    public function getAll()
    {
        $from = new \DateTime('today');
        $to = new \DateTime();
        $result = $this->getSliceValues($from, $to);

        $format = new Format();
        // Sort by value
        foreach ($result as &$values) {
            usort($values, function ($a, $b) {
                return $b['total'] - $a['total'];
            });
            foreach ($values as &$value) {
                $value['total'] = $format->shorten($value['total']);
            }
        }

        return $this->jsonResponse(['slices' => $result]);
    }

    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $queryParams = $request->getQueryParams();
        $metricId = isset($attributes['metric_id']) ? (int)$attributes['metric_id'] : null;

        $format = new Format();

        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('-1 day');
        $from->setTime(0, 0, 0);
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();
        $to->setTime(23, 59, 59);

        if (!$metricId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ')');
        }

        $result = $this->getSliceValues($from, $to, $metricId);

        // Sort by value
        foreach ($result as &$group) {
            usort($group, function ($a, $b) {
                return $b['total'] - $a['total'];
            });
            foreach ($group as &$value) {
                $value['total'] = $format->shorten($value['total']);
            }
        }

        return $this->jsonResponse(['slices' => $result]);
    }

    private function getSliceValues(\DateTime $from, \DateTime $to, $metricId = null)
    {
        $result = [];

        $todayTimestamp = strtotime(date('Y-m-d', strtotime('-1 day')));
        $tomorrowTimestamp = strtotime('tomorrow 00:00:00', strtotime('-6 hour'));
        $yesterdayTimestamp = strtotime('yesterday 00:00:00', strtotime('-6 hour'));
        if ($from->getTimestamp() >= $tomorrowTimestamp) {
            return $result;
        }
        $periodDiff = $from->diff($to);
        $periodDiffDays = (int)$periodDiff->format('%a') + 1;

        if ($to->getTimestamp() < $todayTimestamp) {
            //get data from monthly tables
            $result = $this->getFormattedTotalsFromMonthlySlices($from, $to, $periodDiffDays, $metricId);
        } else {
            //get data from daily tables for today due to no data in monthly tables

            $dt = new \DateTime('-6 hour');
            $pastDt = new \DateTime('-6 hour');
            $pastDt->modify('-' . $periodDiffDays . ' day');

            //select totals from daily tables
            $dailyTotals = $this->getTotalsFromDailySlices($dt, $pastDt, $metricId);
            if ($from->getTimestamp() > $yesterdayTimestamp) {
                $result = $this->formatTotals($dailyTotals['currentSubtotals'], $dailyTotals['pastSubtotals']);
            } else {
                //select totals from monthly table
                $monthlyTotals = $this->getTotalsFromMonthlySlices(
                    $from,
                    new \DateTime('yesterday 23:59:59'),
                    $periodDiffDays,
                    $metricId
                );
                $mergedTotals = $this->mergeTotals($dailyTotals, $monthlyTotals);
                $result = $this->formatTotals($mergedTotals['currentSubtotals'], $mergedTotals['pastSubtotals']);
            }
        }

        return $result;
    }

    /**
     * Reformat array as ['$slice_id'=> [...]]
     * @param array $subtotals
     * @return array
     */
    private function prepareSubtotals(array $subtotals)
    {
        $result = [];
        foreach ($subtotals as $subtotal) {
            $index = $subtotal['slice_id'];
            unset($subtotal['slice_id']);
            $result[$index] = $subtotal;
        }
        return $result;
    }

    private function getTotalsFromDailySlices(\DateTime $dt, \DateTime $pastDt, $metricId = null): array
    {
        $currentSubtotals = [];
        $pastSubtotals = [];
        $currentTimestamp = time();
        $currentDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d');
        $pastDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $pastDt->format('Y_m_d');
        try {
            $currentSubtotals = $this->mysql->dailySlices
                ->setTable($currentDailySlicesTableName)
                ->getTotals($currentTimestamp, $metricId, true);
            $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
            $pastSubtotals = $this->mysql->dailySlices
                ->setTable($pastDailySlicesTableName)
                ->getTotals($currentTimestamp, $metricId, false);
            $pastSubtotals = $this->prepareSubtotals($pastSubtotals);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '42S02') { //table does not exists
                throw $exception;
            }
        }
        return [
            'currentSubtotals' => $currentSubtotals,
            'pastSubtotals' => $pastSubtotals,
        ];
    }

    private function formatTotals(array $currentPeriodSubtotals, array $pastPeriodSubtotals): array
    {
        $result = [];
        foreach ($currentPeriodSubtotals as $sliceId => $currentPeriodSubtotal) {
            $data = [
                'id' => $sliceId,
                'name' => $currentPeriodSubtotal['name'],
                'total' => $currentPeriodSubtotal['value'],
            ];

            if (isset($pastPeriodSubtotals[$sliceId])) {
                $pastValue = $pastPeriodSubtotals[$sliceId]['value'];
                if ($pastValue == 0){
                    $data['diff'] = true;
                } else {
                    $data['diff'] = (($currentPeriodSubtotal['value'] * 100) / $pastValue) - 100;
                }
            }
            $result[$currentPeriodSubtotal['category']][] = $data;
        }
        return $result;
    }

    private function getTotalsFromMonthlySlices(
        \DateTime $from,
        \DateTime $to,
        int $periodDiffDays,
        $metricId = null
    ): array
    {
        $pastFrom = clone $from;
        $pastTo = clone $to;
        $pastFrom = $pastFrom->modify('-' . $periodDiffDays . ' day');
        $pastTo = $pastTo->modify('-' . $periodDiffDays . ' day');
        $currentSubtotals = $this->mysql->monthlySlices
            ->getTotals($from, $to, $metricId,true);
        $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
        $pastSubtotals = $this->mysql->monthlySlices
            ->getTotals($pastFrom, $pastTo, $metricId, false);
        $pastSubtotals = $this->prepareSubtotals($pastSubtotals);
        return [
            'currentSubtotals' => $currentSubtotals,
            'pastSubtotals' => $pastSubtotals,
        ];
    }

    private function getFormattedTotalsFromMonthlySlices(\DateTime $from, \DateTime $to, int $periodDiffDays, $metricId = null): array
    {
        $totals = $this->getTotalsFromMonthlySlices($from, $to, $periodDiffDays, $metricId);
        $result = $this->formatTotals($totals['currentSubtotals'], $totals['pastSubtotals']);
        return $result;
    }

    private function mergeTotals(array $dailyTotals, array $monthlyTotals): array
    {
        $result = [];
        foreach ($monthlyTotals as $key => $monthlyTotal) {
            $result[$key] = [];
            foreach ($monthlyTotal as $sliceId => $value) {
                if (!isset($dailyTotals[$key][$sliceId])) {
                    continue;
                }
                $value['value'] += $dailyTotals[$key][$sliceId]['value'];
                $result[$key][$sliceId] = $value;
            }
        }

        return $result;
    }
}