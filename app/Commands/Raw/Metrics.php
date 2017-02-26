<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Metrics extends AbstractCommand
{
    const COUNTER_NAME = 'raw_metrics';

    protected function configure()
    {
        $this
            ->setName('raw:metrics')
            ->setDescription('TBD')
            ->setHelp('This command allows you to...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = date('Y-m-d H:i:s');
        $lastCounter = $this->mysql->dailyCounters->getValue(static::COUNTER_NAME);
        $startId = $lastCounter ? $lastCounter + 1 : 0;

        // Getting maxId bc no order in aggr result
        $maxId = $this->mysql->dailyRawMetrics->getMaxIdForTime($time, $startId);
        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);

        // Getting grouped data form RAW
        $aggregatedData = $this->mysql->dailyRawMetrics->getAggregatedDataByRange($time, $startId, $maxId);
        if (!count($aggregatedData)) {
            $output->writeln('No raw data in dailyRawMetrics from startId ' . $startId);
            return;
        }

        var_dump($aggregatedData);

        // Saving into aggr table
        $saved = 0;
        foreach ($aggregatedData as $row) {
            $res = $this->mysql->dailyMetrics->insert($row);
            if ($res) {
                $saved++;
            }
        }

        $output->writeln("Saved {$saved} daily metrics");
    }

}