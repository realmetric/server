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
        sleep(3);//prevent supervisord exited too quick error
        while (1) {
            $added = 0;
            do {
                $res = (int)$this->track(time());
                $added += $res;
            } while ($res);
            $this->out('Added: ' . $added);
            sleep(10);
            $memory = memory_get_usage();
            if ($memory > 262144000){
                $this->out('Memory usage more then 250MB: '. number_format($memory / 1024 / 1024, 2) . 'MB');
                die;
            }
        }
    }

    private function track($timestamp)
    {
        $eventPack = $this->redis->track_raw->sPop();
        if (!$eventPack) {
            return 0;
        }
        $rawEvents = json_decode($eventPack, true);

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
                if ($category === null || $slice === null || is_array($slice)) {
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
        return (int)$eventService->saveBatch($events, $metrics, $categories, $slices, $timestamp);
    }
}