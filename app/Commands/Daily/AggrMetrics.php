<?php

namespace App\Commands\Daily;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggrMetrics extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timeStart = time();
            $saved = $this->flush($timeStart);
            $this->out("Aggregated {$saved} daily metrics");
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

        $dailyMetricTotalsTable = $this->mysql->dailyMetricTotals->setTableFromTimestamp($timestamp)->getTable();
        $dailyMetricsTable = $this->mysql->dailyMetrics->setTableFromTimestamp($timestamp)->getTable();
        $dailyMetricsYesterdayTable = $this->mysql->dailyMetrics->setTableFromTimestamp(strtotime('-1 day', $timestamp))->getTable();

        $sql = <<<SQL
INSERT INTO $dailyMetricTotalsTable (metric_id, value, diff)
  SELECT s.metric_id, s.val, (case s.sm
                                          when 0 then CAST(0 AS DECIMAL(8,2))
                                          else CAST(((s.val * 100) / s.sm) - 100 AS DECIMAL(8,2)) END) AS diff
  FROM (SELECT
          daily_metrics.metric_id,
          sum(daily_metrics.value) val,
          case when df.sm is null then 0
    else df.sm end as sm
        FROM $dailyMetricsTable daily_metrics
          LEFT JOIN (SELECT
                       daily_metrics_diff.metric_id,
                       sum(value) AS sm
                     FROM $dailyMetricsYesterdayTable daily_metrics_diff
                     WHERE daily_metrics_diff.minute < $minute
                     GROUP BY daily_metrics_diff.metric_id
                    ) df ON daily_slices.metric_id = df.metric_id
        GROUP BY daily_metrics.metric_id) s
ON DUPLICATE KEY UPDATE
  $dailyMetricTotalsTable.value = s.val,
  $dailyMetricTotalsTable.diff  = diff
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