<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use App\Models\DailyCountersModel;
use App\Models\DailyRawSlicesModel;
use App\Models\DailySlicesModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Slices extends AbstractCommand
{
    const COUNTER_NAME = 'raw_slices';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $this->mysql->dailyCounters
//            ->setTable(DailyCountersModel::TABLE_PREFIX . date('Y_m_d_H', strtotime('2017-07-26 11:02:00')))
//            ->createTable(DailyCountersModel::TABLE_PREFIX . date('Y_m_d_H', strtotime('2017-07-26 11:02:00')));
//        die;
//        sleep(3);//prevent supervisord exited too quick error
//        foreach (['2017-07-27 11:01:00', '2017-07-27 11:02:00', '2017-07-27 11:03:00'] as $time) {
//            $beforeMemory = memory_get_usage();
//            $saved = $this->flush($time);
//            $afterMemoryUsage = memory_get_usage();
//            $this->out("Saved {$saved} daily slices");
//            $this->out($beforeMemory . ' / ' . $afterMemoryUsage);
//        }
        while (1) {
            $timeStart = time();
            $saved = $this->flush($timeStart);
            $this->out("Saved {$saved} daily slices");
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    private function flush($timestamp, $skipTimeForMaxId = false)
    {
        $lastCounter = $this->mysql->dailyCounters
            ->setTableFromTimestamp($timestamp)
            ->getValue(static::COUNTER_NAME);
        if ($lastCounter == 0){
            $this->flush(strtotime('-1 hour', $timestamp), true);
        }

        $startId = $lastCounter ? $lastCounter + 1 : 0;

        if ($skipTimeForMaxId){
            $maxId = $this->mysql->dailyRawSlices
                ->setTableFromTimestamp($timestamp)
                ->getMaxId();
        } else {
            // Getting maxId bc no order in aggr result
            $maxId = $this->mysql->dailyRawSlices
                ->setTableFromTimestamp($timestamp)
                ->getMaxIdForTime($timestamp);

        }

        if ($maxId < $lastCounter) {
            $this->out('No new records in dailyRawSlices from startId ' . $maxId);
            if (!$skipTimeForMaxId){
                sleep(5);
            }
            return 0;
        }

        $this->mysql->dailyCounters
            ->setTableFromTimestamp($timestamp)
            ->updateOrInsert(static::COUNTER_NAME, $maxId);

        $slicesAggrPdoStmt = $this->aggregate($startId, $maxId, $timestamp);


//        if ($slicesAggr){
//            $totalsAggr = $this->mysql->dailySlices->aggregate($startId, $maxIdForTime, strtotime($time));
//        }

        // Getting grouped data form RAW
//        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($startId, $maxIdForTime);
//        if (!$aggregatedData) {
//            $this->out('No raw data in dailyRawSlices from startId ' . $startId);
//            sleep(5);
//            return 0;
//        }
//
//        // Saving into aggr table
//        $todayDailySliceTableName = DailySlicesModel::TABLE_PREFIX . date('Y_m_d');
//        $yesterdayDailySliceTableName = DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day'));
//
//        $saved = 0;
//        foreach ($aggregatedData as $row) {
//            $res = false;
//            try {
//                $res = $this->mysql->dailySlices
//                    ->setTable($todayDailySliceTableName)
//                    ->createOrIncrement($row['metric_id'], $row['slice_id'], $row['value'], $row['minute']);
//
//                $diff = $this->mysql->dailySlices
//                    ->setTable($yesterdayDailySliceTableName)
//                    ->getDiff($row['metric_id'], $row['slice_id'], $row['value'], $row['minute']);
////                $diff=0;
//                $this->mysql->dailySliceTotals->addValue($row['metric_id'], $row['slice_id'], $row['value'], $diff);
//            } catch (\Exception $e) {
//                $this->out($e->getMessage());
//            }
//
//            if ($res) {
//                $saved++;
//            }
//        }

        return $slicesAggrPdoStmt->rowCount();
    }

    private function aggregate(int $startId, int $maxIdForTime, int $timestamp) : \PDOStatement
    {
        $dailySlicesTable = $this->mysql->dailySlices->setTableFromTimestamp($timestamp)->getTable();
        $dailyRawSlicesTable = $this->mysql->dailyRawSlices->setTableFromTimestamp($timestamp)->getTable();

        $sql = <<<SQL
INSERT INTO $dailySlicesTable (metric_id, slice_id, value, minute)
  SELECT * FROM (SELECT
    daily_raw_slices.metric_id,
    daily_raw_slices.slice_id,
    sum(daily_raw_slices.value) val,
    daily_raw_slices.minute
  FROM $dailyRawSlicesTable daily_raw_slices
  WHERE daily_raw_slices.id >= $startId
        AND daily_raw_slices.id <= $maxIdForTime
  GROUP BY daily_raw_slices.metric_id,
    daily_raw_slices.slice_id,
    daily_raw_slices.minute) s
ON DUPLICATE KEY UPDATE
  $dailySlicesTable.value = $dailySlicesTable.value + s.val
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