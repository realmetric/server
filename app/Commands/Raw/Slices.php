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
        echo $time . PHP_EOL;

        $lastCounter = $this->mysql->dailyCounters->getByName(static::COUNTER_NAME);

        if ($lastCounter){
            $startId = $lastCounter['value'] + 1;
        } else {
            $startId = 0;
        }
        $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($time, $startId);
        if (!$aggregatedData){
            return;
        }

        foreach($aggregatedData as $row){
            $this->mysql->dailySlices->insert($row);
        }

        $maxId = $this->mysql->dailyRawSlices->getAggregatedMaxId($time);
        if (!$maxId){
            return;
        }

        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);
    }
}