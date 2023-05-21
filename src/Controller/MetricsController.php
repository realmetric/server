<?php


namespace App\Controller;

use App\Library\EventSaver;
use App\Library\Format;
use App\Model\DailyMetricsModel;
use App\Model\DailyMetricTotalsModel;
use Illuminate\Database\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    public function __construct(
        private readonly EventSaver             $eventSaver,
        private readonly DailyMetricsModel      $dailyMetrics,
        private readonly DailyMetricTotalsModel $dailyMetricTotals
    )
    {
    }

    #[Route('/metrics', methods: ['GET'])]
    public function getAll()
    {
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'all metrics']);

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
        return $this->json(['metrics' => $result]);
    }

    #[Route('/metrics/slice/{sliceId}', methods: ['GET'])]
    public function getBySliceId(int $sliceId)
    {
        throw new \Exception('not implemented');
    }

    #[Route('/metrics/{metricId}', methods: ['GET'])]
    public function getByMetricId(int $metricId)
    {
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'metric by id']);

        $data = $this->dailyMetrics->getByMetricId($metricId);
        $yesterdayData = $this->dailyMetrics
            ->setTable(DailyMetricsModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day')))
            ->getByMetricId($metricId);

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

        return $this->json(['values' => $values]);
    }

    private function getMetricValues(\DateTime $from, \DateTime $to)
    {
        $totals = $this->dailyMetricTotals->getTotals(true);
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
            $currentSubtotals = $this->dailyMetrics
                ->setTable($currentDailySlicesTableName)
                ->getTotals($currentTimestamp, true);
            $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
            $pastSubtotals = $this->dailyMetrics
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
