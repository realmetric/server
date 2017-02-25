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

        // Find last selected id
        $lastCounter = $this->mysql->dailyCounters->getByName(static::COUNTER_NAME);
        if ($lastCounter && $lastCounter['value']) {
            $startId = $lastCounter['value'] + 1;
        } else {
            $startId = 0;
        }

        // Select range
        $aggregatedRange = $this->mysql->dailyRawSlices->getAggregatedRange($time, $startId);
        if (!count($aggregatedRange)) {
            $output->writeln('No raw data in dailyRawMetrics for range');
            return;
        }
        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $aggregatedRange['max_id']);

        // Get raw data
        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($time, $aggregatedRange['min_id'], $aggregatedRange['max_id']);
        if (!$aggregatedData) {
            $output->writeln('No raw data in dailyRawSlices from startId ' . $startId);
            return;
        }

        // Write to dailySlices
        $saved = 0;
        foreach ($aggregatedData as $row) {
            $res = $this->mysql->dailySlices->insert($row);
            if ($res) {
                $saved++;
            }
        }

        $output->writeln("Saved {$saved} daily slices");
    }
}