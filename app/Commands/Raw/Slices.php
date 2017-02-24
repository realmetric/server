<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Slices extends AbstractCommand
{
    const COUNTER_NAME = 'raw_slices';

    protected function configure()
    {
        $this
            ->setName('raw:slices')
            ->setDescription('TBD')
            ->setHelp('This command allows you to...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = date('Y-m-d H:i:s');

        $lastCounter = $this->mysql->dailyCounters->getByName(static::COUNTER_NAME);

        if ($lastCounter && $lastCounter['value']) {
            $startId = $lastCounter['value'] + 1;
        } else {
            $startId = 0;
        }

        $aggregatedRange = $this->mysql->dailyRawSlices->getAggregatedRange($time, $startId);
        if (!count($aggregatedRange)) {
            $output->writeln('No raw data in dailyRawMetrics for range');
            return;
        }
        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($time, $aggregatedRange['min_id'], $aggregatedRange['max_id']);
        if (!$aggregatedData) {
            $output->writeln('No raw data in dailyRawSlices from startId ' . $startId);
            return;
        }

        foreach ($aggregatedData as $row) {
            $this->mysql->dailySlices->insert($row);
        }

        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $aggregatedRange['max_id']);
    }
}