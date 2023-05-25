<?php


namespace App\Controller;

use App\Library\EventSaver;
use App\Library\Format;
use App\Model\DailyMetricsModel;
use App\Model\MonthlyMetricsModel;
use App\Model\MonthlySlicesModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    public function __construct(
        private readonly EventSaver            $eventSaver,
        private readonly DailyMetricsModel     $dailyMetrics,
        private readonly MonthlyMetricsModel   $monthlyMetrics,
        private readonly MonthlySlicesModel    $monthlySlicesModel
    )
    {
    }

    #[Route('/metrics', methods: ['GET'])]
    public function getAll()
    {
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'all metrics']);
        $totals = $this->monthlyMetrics->getTodayTotals();
        $result = [];
        foreach ($totals as $row) {
            $nameParts = explode('.', $row['name']);
            $catName = count($nameParts) > 1 ? $nameParts[0] : 'Other';
            $result[$catName][] = $row;
        }
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
        $this->eventSaver->save('RealmetricVisits', 1, time(), ['page' => 'metrics by sliceId']);

        $metricsWithSlice = $this->monthlySlicesModel->getTodayTotals(sliceId: $sliceId);

        $totals = $this->monthlyMetrics->getTodayTotals();
        $result = [];
        foreach ($totals as $row) {
            if (!in_array($row['id'], $metricsWithSlice)) {
                continue;
            }
            $nameParts = explode('.', $row['name']);
            $catName = count($nameParts) > 1 ? $nameParts[0] : 'Other';
            $result[$catName][] = $row;
        }
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
}
