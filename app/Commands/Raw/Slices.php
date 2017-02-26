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
        $lastCounter = $this->mysql->dailyCounters->getValue(static::COUNTER_NAME);
        $startId = $lastCounter ? $lastCounter + 1 : 0;

        // Getting maxId bc no order in aggr result
        $maxId = $this->mysql->dailyRawSlices->getMaxIdForTime($time);
        if ($maxId < $startId) {
            $output->writeln('No new records in dailyRawSlices from startId ' . $startId);
            return;
        }
        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);

        // Getting grouped data form RAW
        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($time, $startId, $maxId);
        if (!$aggregatedData) {
            $output->writeln('No raw data in dailyRawSlices from startId ' . $startId);
            return;
        }

        // Saving into aggr table
        $saved = 0;
        foreach ($aggregatedData as $row) {
            $res = $this->mysql->dailySlices->insert($row);
            if ($res) {
                $saved++;
            }
        }

        $output->writeln("Saved {$saved} daily slices. MaxId: {$maxId}");
    }
}