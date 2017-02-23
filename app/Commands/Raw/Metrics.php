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

        $lastCounter = $this->mysql->dailyCounters->getByName(static::COUNTER_NAME);

        if ($lastCounter) {
            $startId = $lastCounter['value'] + 1;
        } else {
            $startId = 0;
        }

        $aggregatedData = $this->mysql->dailyRawMetrics->getAggregatedData($time, $startId);
        if (!$aggregatedData) {
            $output->writeln('No raw data in dailyRawMetrics from startId ' . $startId);
            return;
        }


        $maxId = 0;
        foreach ($aggregatedData as $row) {
            $this->mysql->dailyMetrics->insert($row);
            if ($maxId < (int)$row['id']) {
                $maxId = (int)$row['id'];
            }
        }

        if (!$maxId) {
            return;
        }

        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);
    }

}