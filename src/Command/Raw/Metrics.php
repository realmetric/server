<?php

namespace App\Command\Raw;


use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Metrics extends AbstractCommand
{
    const COUNTER_NAME = 'raw_metrics';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        sleep(3);//prevent supervisord exited too quick error
        while (1) {
            $timeStart = time();
            $saved = $this->fetch($timeStart);
            $this->out("Saved {$saved} daily metrics");

            if (!$saved) {
                mail('andreinwald@gmail.com', 'No metrics in Realmetric ' . time(), '');
            }

            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
            $memory = memory_get_usage();
            if ($memory > 262144000){
                $this->out('Memory usage more then 250MB: '. number_format($memory / 1024 / 1024, 2) . 'MB');
                die;
            }
        }
    }

    private function fetch(int $timestamp)
    {
        $lastCounter = $this->mysql->dailyCounters
            ->setTableFromTimestamp($timestamp)
            ->getValue(static::COUNTER_NAME);
        $startId = $lastCounter ? $lastCounter + 1 : 0;

        // Getting maxId bc no order in aggr result
        $maxId = $this->mysql->dailyRawMetrics
            ->setTableFromTimestamp($timestamp)
            ->getMaxIdForTime($timestamp);
        if ($maxId < $startId) {
            $this->out('No new records in dailyRawMetrics from startId ' . $startId);
            sleep(5);
            return 0;
        }
        $this->mysql->dailyCounters
            ->setTableFromTimestamp($timestamp)
            ->updateOrInsert(static::COUNTER_NAME, $maxId);
        $this->out($startId . ' - ' . $maxId);

        // Getting grouped data from RAW
        $aggregatedData = $this->mysql->dailyRawMetrics
            ->setTableFromTimestamp($timestamp)
            ->getAggregatedDataByRange($startId, $maxId);
        if (!count($aggregatedData)) {
            $this->out('No raw data in dailyRawMetrics from startId ' . $startId);
            sleep(5);
            return 0;
        }


        // Saving into aggr table
        $saved = 0;
        foreach ($aggregatedData as $row) {
            $res = false;
            try {
                $res = $this->mysql->dailyMetrics
                    ->setTableFromTimestamp($timestamp)
                    ->createOrIncrement($row['metric_id'], $row['value'], $row['minute']);
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