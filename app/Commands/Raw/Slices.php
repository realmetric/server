<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use App\Models\DailySlicesModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Slices extends AbstractCommand
{
    const COUNTER_NAME = 'raw_slices';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timeStart = time();
            $saved = $this->flush();
            $this->out("Saved {$saved} daily slices");
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
            if (!random_int(0, 9)) {
                die;
            }
        }
    }

    private function flush()
    {
        $time = date('Y-m-d H:i:s');
        $lastCounter = $this->mysql->dailyCounters->getValue(static::COUNTER_NAME);
        $startId = $lastCounter ? $lastCounter + 1 : 0;

        // Getting maxId bc no order in aggr result
        $maxId = $this->mysql->dailyRawSlices->getMaxIdForTime($time);
        if ($maxId < $startId) {
            $this->out('No new records in dailyRawSlices from startId ' . $startId);
            sleep(5);
            return 0;
        }
        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);

        // Getting grouped data form RAW
        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($startId, $maxId);
        if (!$aggregatedData) {
            $this->out('No raw data in dailyRawSlices from startId ' . $startId);
            sleep(5);
            return 0;
        }

        // Saving into aggr table
        $saved = 0;
        $todayDailySliceTableName = DailySlicesModel::TABLE_PREFIX . date('Y_m_d');
        $yesterdayDailySliceTableName = DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day'));
        foreach ($aggregatedData as $row) {
            $res = false;
            try {
                $res = $this->mysql->dailySlices
                    ->setTable($todayDailySliceTableName)
                    ->createOrIncrement($row['metric_id'], $row['slice_id'], $row['value'], $row['minute']);

                $diff = $this->mysql->dailySlices
                    ->setTable($yesterdayDailySliceTableName)
                    ->getDiff($row['metric_id'], $row['slice_id'], $row['value'], $row['minute']);
                $this->mysql->dailySliceTotals->addValue($row['metric_id'], $row['slice_id'], $row['value'], $diff);
            } catch (\Exception $e) {
                $this->out($e->getMessage());
            }

            if ($res) {
                $saved++;
            }
        }
        return $saved;
    }
}