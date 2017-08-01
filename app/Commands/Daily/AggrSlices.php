<?php

namespace App\Commands\Daily;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggrSlices extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timeStart = time();
            $saved = $this->flush($timeStart);
            $this->out("Aggregated {$saved} daily slices");
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    protected function flush($timestamp)
    {
        $totalsAggrStmt = $this->aggregate($timestamp);
        return $totalsAggrStmt->rowCount();
    }

    protected function aggregate(int $timestamp)
    {
        $minute = date('H', $timestamp) * 60 + date('i', $timestamp);

        $dailySliceTotalsTable = $this->mysql->dailySliceTotals->setTableFromTimestamp($timestamp)->getTable();
        $dailySlicesTable = $this->mysql->dailySlices->setTableFromTimestamp($timestamp)->getTable();
        $dailySlicesYesterdayTable = $this->mysql->dailySlices->setTableFromTimestamp(strtotime('-1 day', $timestamp))->getTable();

        $sql = <<<SQL
INSERT INTO $dailySliceTotalsTable (metric_id, slice_id, value, diff)
  SELECT s.metric_id, s.slice_id, s.val, (case s.sm
                                          -- when s.sm IS NULL then 0
                                          when 0 then CAST(0 AS DECIMAL(8,2))
                                          else CAST(((s.val * 100) / s.sm) - 100 AS DECIMAL(8,2)) END) AS diff
  FROM (SELECT
          daily_slices.metric_id,
          daily_slices.slice_id,
          sum(daily_slices.value) val,
          case when df.sm is null then 0
    else df.sm end as sm
        FROM $dailySlicesTable daily_slices
          LEFT JOIN (SELECT
                       daily_slices_diff.metric_id,
                       daily_slices_diff.slice_id,
                       sum(value) AS sm
                     FROM $dailySlicesYesterdayTable daily_slices_diff
                     WHERE daily_slices_diff.minute < $minute
                     GROUP BY daily_slices_diff.metric_id, daily_slices_diff.slice_id
                    ) df ON daily_slices.metric_id = df.metric_id AND
                            daily_slices.slice_id = df.slice_id
        GROUP BY daily_slices.metric_id,
          daily_slices.slice_id) s
ON DUPLICATE KEY UPDATE
  $dailySliceTotalsTable.value = s.val,
  $dailySliceTotalsTable.diff  = diff
SQL;

        try {
            $result = $this->mysql->getConnection()->getPdo()->query($sql, \PDO::FETCH_ASSOC);
        } catch (\Exception $ex){
            $this->out($sql);
            throw $ex;
        }
        return $result;
    }

}