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
        $added = 0;
        do {
            $res = (int)$this->track();
            $added += $res;
        } while ($res);
        $this->output->writeln('Added: ' . $added);
    }

    private function track()
    {
        $rawEvents = [];
        $i = 0;
        while (($eventPack = $this->redis->sPop(Keys::REDIS_SET_TRACK_QUEUE)) && $i < 500) {
            $pack = json_decode($eventPack, true);
            foreach ($pack as $event) {
                $rawEvents[] = $event;
            }
            $i++;
        }

        if (!count($rawEvents)) {
            return 0;
        }

        $events = [];
        $metrics = [];
        $categories = [];
        $slices = [];

        foreach ($rawEvents as $data) {
            $event = [
                't' => $data['time'],
                'v' => $data['value'],
            ];

            // Metric
            $metricId = array_search($data['metric'], $metrics, true);
            if ($metricId === false) {
                $metrics[] = $data['metric'];
                $metricId = count($metrics) - 1;
            }
            $event['m'] = $metricId;

            // Slices
            if (!isset($data['slices'])) {
                $events[] = $event;
                continue;
            }

            foreach ($data['slices'] as $category => $slice) {
                if ($category === null || $slice === null) {
                    continue;
                }

                $categoryId = array_search($category, $categories, true);
                if ($categoryId === false) {
                    $categories[] = $category;
                    $categoryId = count($categories) - 1;
                }

                $sliceId = array_search($slice, $slices, true);
                if ($sliceId === false) {
                    $slices[] = $slice;
                    $sliceId = count($slices) - 1;
                }

                $event['s'][] = [$categoryId, $sliceId];
            }

            $events[] = $event;
        }

        $eventService = new Event();
        return (int)$eventService->saveBatch($events, $metrics, $categories, $slices);
    }
}