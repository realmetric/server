<?php


namespace App\Controllers;

use App\Models\DailySlicesModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class TotalsController extends AbstractController
{
    public function minutes(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;

        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('-1 day');
        $from->setTime(0, 0, 0);
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();
        $to->setTime(23, 59, 59);

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($from, $interval, $to);

        if (!$metricId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ')');
        }

        $values = $this->getSliceValuesByMinutes($metricId, $period);

        return $this->jsonResponse(['slices' => $values]);
    }

    protected function getSliceValuesByMinutes(int $metricId, \DatePeriod $period)
    {
        $result = [];

        $todayTimestamp = strtotime(date('Y-m-d'));
        $tomorrowTimestamp = strtotime('tomorrow');
        if ($period->getStartDate()->getTimestamp() >= $tomorrowTimestamp){
            return $result;
        }
        if ($period->getEndDate()->getTimestamp() < $todayTimestamp){
            //get data from monthly tables
            $result = $this->getTotalsFromMonthlySlices($metricId, $period);
        } else {
            //get data from daily tables for today due to no data in monthly tables
            $periodDiff = $period->getStartDate()->diff($period->getEndDate());
            $periodDiffDays = (int)$periodDiff->format('%a') + 1;
            $dt = new \DateTime();
            $pastDt = clone $dt;
            $pastDt->modify('-' . $periodDiffDays . ' day');

            //select totals from daily tables
            $result[$dt->format('Y-m-d')] = $this->getTotalsFromDailySlices($metricId, $dt, $pastDt);
            //select totals from monthly table
            $interval = \DateInterval::createFromDateString('1 day');
            $cuttedPeriod = new \DatePeriod($period->getStartDate(), $interval, new \DateTime('yesterday 23:59:59'));
            $cuttedResult = $this->getTotalsFromMonthlySlices($metricId, $cuttedPeriod);
            $result = array_merge($result, $cuttedResult);
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
        foreach ($subtotals as $subtotal)
        {
            $index = $subtotal['slice_id'];
            unset($subtotal['slice_id']);
            $result[$index] = $subtotal;
        }
        return $result;
    }

    protected function prepareDailySubtotals(array $subtotals)
    {
        $result = [];
        foreach ($subtotals as $subtotal)
        {
            $index = $subtotal['slice_id'];
            $date = $subtotal['date'];
            unset($subtotal['slice_id'], $subtotal['date']);
            $result[$date][$index] = $subtotal;
        }
        return $result;
    }

    protected function getTotalsFromDailySlices(int $metricId, \DateTime $dt, \DateTime $pastDt):array
    {
        $currentPeriodSubtotals = [];
        $pastPeriodSubtotals = [];
        $currentTimestamp = time();
        $currentDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d');
        $pastDailySlicesTableName = DailySlicesModel::TABLE_PREFIX . $pastDt->format('Y_m_d');
        try {
            $currentPeriodSubtotals = $this->mysql->dailySlices
                ->setTable($currentDailySlicesTableName)
                ->getTotalsByMetricId($metricId, $currentTimestamp, true);
            $currentPeriodSubtotals = $this->prepareSubtotals($currentPeriodSubtotals);
            $pastPeriodSubtotals = $this->mysql->dailySlices
                ->setTable($pastDailySlicesTableName)
                ->getTotalsByMetricId($metricId, $currentTimestamp, false);
            $pastPeriodSubtotals = $this->prepareSubtotals($pastPeriodSubtotals);
            $result = $this->formatTotals($currentPeriodSubtotals, $pastPeriodSubtotals);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '42S02') { //table does not exists
                throw $exception;
            }
            $result = $this->formatTotals($currentPeriodSubtotals, $pastPeriodSubtotals);
        }
        return $result;
    }

    protected function formatTotals(array $currentPeriodSubtotals, array $pastPeriodSubtotals) : array
    {
        $result = [];
        foreach ($currentPeriodSubtotals as $sliceId => $currentPeriodSubtotal){
            $data = [
                'id' => $sliceId,
                'name' => $currentPeriodSubtotal['name'],
                'total' => $currentPeriodSubtotal['value'],
            ];

            if (isset($pastPeriodSubtotals[$sliceId])){
                $data['diff'] = (($currentPeriodSubtotal['value'] * 100) / $pastPeriodSubtotals[$sliceId]['value']) - 100;
            }
            $result[$currentPeriodSubtotal['category']][] = $data;
        }
        return $result;
    }

    protected function formatDailyTotals(array $currentPeriodDailySubtotals, array $pastPeriodDailySubtotals, int $diffDays) : array
    {
        $result = [];
        foreach ($currentPeriodDailySubtotals as $date => $values){
            $pastDate = date('Y-m-d', strtotime('-' . $diffDays . ' day', strtotime($date)));
            foreach ($values as $sliceId => $currentPeriodSubtotal){
                $data = [
                    'id' => $sliceId,
                    'name' => $currentPeriodSubtotal['name'],
                    'total' => $currentPeriodSubtotal['value'],
                ];

                if (isset($pastPeriodDailySubtotals[$pastDate][$sliceId])){
                    $data['diff'] = (($currentPeriodSubtotal['value'] * 100) / $pastPeriodDailySubtotals[$pastDate][$sliceId]['value']) - 100;
                }
                $result[$date][$currentPeriodSubtotal['category']][] = $data;
            }
        }
        return $result;
    }

    protected function getTotalsFromMonthlySlices(int $metricId, \DatePeriod $period):array
    {
        $periodDiff = $period->getStartDate()->diff($period->getEndDate());
        /**
         * @var $periodClone \DatePeriod
         */
        $periodDiffDays = (int)$periodDiff->format('%a') + 1;
        $pastStartDate = $period->getStartDate()->modify('-' . $periodDiffDays . ' day');
        $pastEndDate = $period->getEndDate()->modify('-' . $periodDiffDays . ' day');
        $interval = \DateInterval::createFromDateString('1 day');
        $pastPeriod = new \DatePeriod($pastStartDate, $interval, $pastEndDate);
        $currentPeriodSubtotals = $this->mysql->monthlySlices
            ->getTotalsByMetricId($metricId, $period, true);
        $currentPeriodSubtotals = $this->prepareDailySubtotals($currentPeriodSubtotals);
        $pastPeriodSubtotals = $this->mysql->monthlySlices
            ->getTotalsByMetricId($metricId, $pastPeriod, false);
        $pastPeriodSubtotals = $this->prepareDailySubtotals($pastPeriodSubtotals);
        $result = $this->formatDailyTotals($currentPeriodSubtotals, $pastPeriodSubtotals, $periodDiffDays);
        return $result;
    }
}