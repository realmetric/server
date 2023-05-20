<?php


namespace App\Controller;

use App\Library\Format;
use App\Model\DailySlicesModel;
use App\Model\DailySliceTotalsModel;
use App\Model\MonthlySlicesModel;
use App\Model\SlicesModel;
use Illuminate\Database\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SlicesController extends AbstractController
{
    public function __construct(
        private readonly SlicesModel $slices,
        private readonly DailySlicesModel $dailySlices,
        private readonly DailySliceTotalsModel $dailySliceTotals,
        private readonly MonthlySlicesModel $monthlySlices,
    )
    {
    }

    #[Route('/slices', methods: ['GET'])]
    public function getAll()
    {
        $result = [];
        $allValues = $this->dailySliceTotals->getAllValues();
        $values = array_column($allValues, 'value', 'id');
        $diffs = array_column($allValues, 'diff', 'id');

        $slices = $this->slices->getAll();
        foreach ($slices as $slice) {
            $sliceId = $slice['id'];
            if (empty($values[$sliceId])) {
                continue;
            }
            $catName = $slice['category'];
            $result[$catName][] = [
                'id' => $sliceId,
                'name' => $slice['name'],
                'total' => $values[$sliceId],
                'diff' => isset($diffs[$sliceId]) ? $diffs[$sliceId] : 0,
            ];
        }

        // Sort by value & formatting
        ksort($result);
        $format = new Format();
        foreach ($result as &$values) {
            usort($values, function ($a, $b) {
                return $b['total'] - $a['total'];
            });
            foreach ($values as &$value) {
                $value['total'] = $format->shorten($value['total']);
            }
        }

        return $this->json(['slices' => $result]);
    }

    #[Route('/slices/{metricId}', methods: ['GET'])]
    public function getByMetricId(int $metricId)
    {
        $format = new Format();
        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime();
        $from->setTime(0, 0, 0);
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();
        $to->setTime(23, 59, 59);

        if (!$metricId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ')');
        }

        $result = $this->getSliceValues($from, $to, $metricId);
        ksort($result);

        // Sort by value
        foreach ($result as &$group) {
            usort($group, function ($a, $b) {
                return $b['total'] - $a['total'];
            });
            foreach ($group as &$value) {
                $value['total'] = $format->shorten($value['total']);
            }
        }

        return $this->json(['slices' => $result]);
    }

    private function getSliceValues(\DateTime $from, \DateTime $to, $metricId = null)
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
            $result = $this->getFormattedTotalsFromMonthlySlices($from, $to, $periodDiffDays, $metricId);
        } else {
            //get data from daily tables for today due to no data in monthly tables

            $dt = new \DateTime();
            $pastDt = new \DateTime();
            $pastDt->modify('-' . $periodDiffDays . ' day');

            if ($from->getTimestamp() > $yesterdayTimestamp) {
                $result = $this->getTotalsFromDailySliceTotals($dt, $metricId);
            } else {
                $dailyTotals = $this->getTotalsFromDailySlices($dt, $pastDt, $metricId);
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
            $currentSubtotals = $this->dailySlices
                ->setTable($currentDailySlicesTableName)
                ->getTotals($currentTimestamp, $metricId, true);
            $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
            $pastSubtotals = $this->dailySlices
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

    private function getTotalsFromDailySliceTotals(\DateTime $dt, $metricId = null): array
    {
        $currentSliceTotalsTable = DailySliceTotalsModel::TABLE_PREFIX . $dt->format('Y_m_d');
        $result = [];
        try {
            $currentSubtotals = $this->dailySliceTotals
                ->setTable($currentSliceTotalsTable)
                ->getTotals($metricId, true);
            $result = [];
            foreach ($currentSubtotals as $row) {
                $category = $row['category'];
                unset($row['category']);
                $result[$category][] = $row;
            }

        } catch (QueryException $exception) {
            if ($exception->getCode() !== '42S02') { //table does not exists
                throw $exception;
            }
        }

        return $result;
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

            if (!empty($currentPeriodSubtotal['diff'])) {
                $data['diff'] = $currentPeriodSubtotal['diff'];
            } elseif (isset($pastPeriodSubtotals[$sliceId])) {
                $pastValue = $pastPeriodSubtotals[$sliceId]['value'];
                if ($pastValue != 0) {
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
        int       $periodDiffDays,
                  $metricId = null
    ): array
    {
        $pastFrom = clone $from;
        $pastTo = clone $to;
        $pastFrom = $pastFrom->modify('-' . $periodDiffDays . ' day');
        $pastTo = $pastTo->modify('-' . $periodDiffDays . ' day');
        $currentSubtotals = $this->monthlySlices
            ->getTotals($from, $to, $metricId, true);
        $currentSubtotals = $this->prepareSubtotals($currentSubtotals);
        $pastSubtotals = $this->monthlySlices
            ->getTotals($pastFrom, $pastTo, $metricId, false);
        $pastSubtotals = $this->prepareSubtotals($pastSubtotals);
        return [
            'currentSubtotals' => $currentSubtotals,
            'pastSubtotals' => $pastSubtotals,
        ];
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

    private function getFormattedTotalsFromMonthlySlices(\DateTime $from, \DateTime $to, int $periodDiffDays, $metricId = null): array
    {
        $totals = $this->getTotalsFromMonthlySlices($from, $to, $periodDiffDays, $metricId);
        $result = $this->formatTotals($totals['currentSubtotals'], $totals['pastSubtotals']);
        return $result;
    }
}
