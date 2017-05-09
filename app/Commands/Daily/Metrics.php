<?php

namespace App\Commands\Daily;


use App\Commands\AbstractCommand;
use App\Models\DailyMetricsModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Metrics extends AbstractCommand
{
    const COUNTER_NAME = 'daily_metrics';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dailyCounterTimestamp = $this->mysql->counters->getValue(static::COUNTER_NAME);

        $tableNames = $this->getTableNamesByCounter($dailyCounterTimestamp);
        foreach ($tableNames as $tableName){
            $data = $this->mysql->monthlyMetrics->getAggregatedDailyMetricsByTableName($tableName);
            if (!$data){
                continue;
            }
            $saved = 0;
            foreach($data as $row){
                $res = $this->mysql->monthlyMetrics->updateOrInsert($row);
                if ($res) {
                    $saved++;
                }
            }
            $date = $this->mysql->monthlyMetrics->getDateFromDailyMetricsTableName($tableName);
            $this->mysql->counters->updateOrInsert(static::COUNTER_NAME, strtotime($date));
            $output->writeln("Aggregated {$saved} daily metric records from table: {$tableName}");
        }
    }

    protected function getTableNamesByCounter($dailyCounterTimestamp = null){
        return $this->mysql->monthlyMetrics->getUnprocessedDailyMetricTableNames($dailyCounterTimestamp);
    }

}