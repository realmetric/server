<?php


namespace App\Controller;

use App\Library\Format;
use App\Model\DailyMetricsModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class MetricsController extends AbstractController
{
    #[Route('/metrics/slice/{slice_id:\d+}', methods: ['GET'])]
    public function getAll(ServerRequestInterface $request)
    {
        $from = new \DateTime('today');
        $to = new \DateTime();
        $result = $this->getMetricValues($from, $to);
        ksort($result, SORT_STRING);

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

    #[Route('/metrics/{metric_id:\d+}', methods: ['GET'])]
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
        $totals = $this->mysql->dailyMetricTotals->getTotals(true);
        $result = [];
        foreach ($totals as $row) {
            $nameParts = explode('.', $row['name']);
            $catName = count($nameParts) > 1 ? $nameParts[0] : 'Other';
            $result[$catName][] = $row;
        }
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
}