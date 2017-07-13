<?php


namespace App\Controllers;

use App\Models\DailyMetricsModel;
use App\Values\Format;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController extends AbstractController
{
    public function getAll(ServerRequestInterface $request)
    {
        $from = new \DateTime('today');
        $to = new \DateTime();
        $result = $this->getMetricValues($from, $to);

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

        return $this->jsonResponse(['metrics' => $result]);
    }

    public function getByMetricId(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();
        $data = $this->mysql->dailyMetrics->getByMetricId($attributes['metric_id']);
        $yesterdayData = $this->mysql->dailyMetrics
            ->setTable(DailyMetricsModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getByMetricId($attributes['metric_id']);

        $today = [];
        foreach ($data as $record) {
            $today[$record['minute']] = $record['value'];
        }
        $yesterday = [];
        foreach ($yesterdayData as $record) {
            $yesterday[$record['minute']] = $record['value'];
        }

        $values = [
            date('Y-m-d') => (object)$today,
            date('Y-m-d', strtotime('-1 day')) => (object)$yesterday,
        ];

        return $this->jsonResponse(['values' => $values]);
    }

    private function getMetricValues(\DateTime $from, \DateTime $to)
    {
        $dt = new \DateTime();
        $pastDt = new \DateTime();
        $periodDiff = $from->diff($to);
        $periodDiffDays = (int)$periodDiff->format('%a') + 1;
        $pastDt->modify('-' . $periodDiffDays . ' day');
        $dailyTotals = $this->getTotalsFromDailyMetrics($dt, $pastDt);
        $result = $this->formatTotals($dailyTotals['currentSubtotals'], $dailyTotals['pastSubtotals']);
        return $result;
    }

    private function getTotalsFromDailyMetrics(\DateTime $dt, \DateTime $pastDt): array
    {
        $currentSubtotals = [];
        $pastSubtotals = [];
        $currentTimestamp = time();
        $currentDailySlicesTableName = DailyMetricsModel::TABLE_PREFIX . $dt->format('Y_m_d');
        $pastDailySlicesTableName = DailyMetricsModel::TABLE_PREFIX . $pastDt->format('Y_m_d');
        try {
            $currentSubtotals = $this->mysql->dailyMetrics
                ->setTable($currentDailySlicesTableName)
                ->getTotals($currentTimestamp, true);
            $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
            $pastSubtotals = $this->mysql->dailyMetrics
                ->setTable($pastDailySlicesTableName)
                ->getTotals($currentTimestamp, false);
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

    /**
     * Reformat array as ['$metric_id'=> [...]]
     * @param array $subtotals
     * @return array
     */
    private function prepareSubtotals(array $subtotals)
    {
        $result = [];
        foreach ($subtotals as $subtotal) {
            $index = $subtotal['metric_id'];
            unset($subtotal['metric_id']);
            $result[$index] = $subtotal;
        }
        return $result;
    }

    private function formatTotals(array $currentPeriodSubtotals, array $pastPeriodSubtotals): array
    {
        $result = [];
        foreach ($currentPeriodSubtotals as $metricId => $currentPeriodSubtotal) {
            $data = [
                'id' => $metricId,
                'name' => $currentPeriodSubtotal['name'],
                'total' => $currentPeriodSubtotal['value'],
            ];

            if (isset($pastPeriodSubtotals[$metricId])) {
                $pastValue = $pastPeriodSubtotals[$metricId]['value'];
                if ($pastValue != 0){
                    $data['diff'] = (($currentPeriodSubtotal['value'] * 100) / $pastValue) - 100;
                }
            }

            $nameParts = explode('.', $currentPeriodSubtotal['name']);
            $catName = count($nameParts) > 1 ? $nameParts[0] : 'Other';
            $result[$catName][] = $data;
        }
        return $result;
    }
}