<?php


namespace App\Controllers;

use App\Models\DailySlicesModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class SlicesController extends AbstractController
{
    public function getAll()
    {
        $result = [];
        $dailySlices = $this->mysql->dailySlices->getAllSlices();
        $slices = $this->mysql->slices->getAll();
        foreach ($slices as $slice) {
            if (!in_array($slice['id'], $dailySlices)) {
                continue;
            }
            $catName = $slice['category'];
            $result[$catName][] = [
                'id' => $slice['id'],
                'name' => $slice['name'],
            ];
        }

        return $this->jsonResponse(['slices' => $result]);
    }

    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();

        $queryParams = $request->getQueryParams();
        $metricId = isset($attributes['metric_id']) ? (int)$attributes['metric_id'] : null;

        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('-1 day');
        $from->setTime(0, 0, 0);
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();
        $to->setTime(23, 59, 59);

        if (!$metricId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ')');
        }

        $values = $this->getSliceValues($metricId, $from, $to);

        return $this->jsonResponse(['slices' => $values]);
    }

    protected function getSliceValues(int $metricId, \DateTime $from, \DateTime $to)
    {
        $result = [];

        $todayTimestamp = strtotime(date('Y-m-d'));
        $tomorrowTimestamp = strtotime('tomorrow 00:00:00');
        $yesterdayTimestamp = strtotime('yesterday 00:00:00');
        if ($from->getTimestamp() >= $tomorrowTimestamp) {
            return $result;
        }
        $periodDiff = $from->diff($to);
        $periodDiffDays = (int)$periodDiff->format('%a') + 1;

        if ($to->getTimestamp() < $todayTimestamp) {
            //get data from monthly tables
            $result = $this->getFormattedTotalsFromMonthlySlices($metricId, $from, $to, $periodDiffDays);
        } else {
            //get data from daily tables for today due to no data in monthly tables

            $dt = new \DateTime();
            $pastDt = new \DateTime();
            $pastDt->modify('-' . $periodDiffDays . ' day');

            //select totals from daily tables
            $dailyTotals = $this->getTotalsFromDailySlices($metricId, $dt, $pastDt);
            if ($from->getTimestamp() > $yesterdayTimestamp) {
                $result = $this->formatTotals($dailyTotals['currentSubtotals'], $dailyTotals['pastSubtotals']);
            } else {
                //select totals from monthly table
                $monthlyTotals = $this->getTotalsFromMonthlySlices($metricId, $from,
                    new \DateTime('yesterday 23:59:59'), $periodDiffDays);
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
    protected function prepareSubtotals(array $subtotals)
    {
        $result = [];
        foreach ($subtotals as $subtotal) {
            $index = $subtotal['slice_id'];
            unset($subtotal['slice_id']);
            $result[$index] = $subtotal;
        }
        return $result;
    }

    protected function getTotalsFromDailySlices(int $metricId, \DateTime $dt, \DateTime $pastDt): array
    {
        $currentSubtotals = [];
        $pastSubtotals = [];
        $currentTimestamp = time();
        $currentDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d');
        $pastDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $pastDt->format('Y_m_d');
        try {
            $currentSubtotals = $this->mysql->dailySlices
                ->setTable($currentDailySlicesTableName)
                ->getTotalsByMetricId($metricId, $currentTimestamp, true);
            $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
            $pastSubtotals = $this->mysql->dailySlices
                ->setTable($pastDailySlicesTableName)
                ->getTotalsByMetricId($metricId, $currentTimestamp, false);
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

    protected function formatTotals(array $currentPeriodSubtotals, array $pastPeriodSubtotals): array
    {
        $result = [];
        foreach ($currentPeriodSubtotals as $sliceId => $currentPeriodSubtotal) {
            $data = [
                'id' => $sliceId,
                'name' => $currentPeriodSubtotal['name'],
                'total' => $currentPeriodSubtotal['value'],
            ];

            if (isset($pastPeriodSubtotals[$sliceId])) {
//                $data['pastTotal'] = $pastPeriodSubtotals[$sliceId]['value'];
                $data['diff'] = (($currentPeriodSubtotal['value'] * 100) / $pastPeriodSubtotals[$sliceId]['value']) - 100;
            }
            $result[$currentPeriodSubtotal['category']][] = $data;
        }
        return $result;
    }

    protected function getTotalsFromMonthlySlices(
        int $metricId,
        \DateTime $from,
        \DateTime $to,
        int $periodDiffDays
    ): array
    {
        $pastFrom = clone $from;
        $pastTo = clone $to;
        $pastFrom = $pastFrom->modify('-' . $periodDiffDays . ' day');
        $pastTo = $pastTo->modify('-' . $periodDiffDays . ' day');
        $currentSubtotals = $this->mysql->monthlySlices
            ->getTotalsByMetricId($metricId, $from, $to, true);
        $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
        $pastSubtotals = $this->mysql->monthlySlices
            ->getTotalsByMetricId($metricId, $pastFrom, $pastTo, false);
        $pastSubtotals = $this->prepareSubtotals($pastSubtotals);
        return [
            'currentSubtotals' => $currentSubtotals,
            'pastSubtotals' => $pastSubtotals,
        ];
    }

    protected function getFormattedTotalsFromMonthlySlices(int $metricId, \DateTime $from, \DateTime $to, int $periodDiffDays): array
    {
        $totals = $this->getTotalsFromMonthlySlices($metricId, $from, $to, $periodDiffDays);
        $result = $this->formatTotals($totals['currentSubtotals'], $totals['pastSubtotals']);
        return $result;
    }

    protected function mergeTotals(array $dailyTotals, array $monthlyTotals): array
    {
        $result = [];
        foreach ($monthlyTotals as $key => $monthlyTotal) {
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