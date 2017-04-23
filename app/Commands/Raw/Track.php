<?php

namespace App\Commands\Raw;

use App\Biz\Event;
use App\Commands\AbstractCommand;
use App\Keys;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Track extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventService = new Event();
        $added = 0;
        while ($data = $this->redis->sPop(Keys::REDIS_SET_TRACK_QUEUE)) {
            $data = json_decode($data, true);
            $added += (int)$eventService->saveBatch($data['events'], $data['metrics'], $data['categories'], $data['slices']);
        }
        $this->output->writeln('Added: ' . $added);
    }
}