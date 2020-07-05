<?php

namespace App\Commands\Daily;


use App\Commands\AbstractCommand;
use App\Events\Intersect;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SliceIntersect extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timestart = microtime(true);
            $this->process();
            echo 'minute done in ' . (microtime(true) - $timestart) . "\n";
        }
    }

    private function process()
    {
        $intersect = new Intersect();

        $timestamp = time() - 120;
        $minute = date('H', $timestamp) * 60 + date('i', $timestamp);

        // @TODO WRONG INTERSECT between metric and slices - already aggregated
        $metrics = $this->mysql->dailyMetrics->getMetricsByMinute($minute);
        foreach ($metrics as $metricId) {
            $slicesAll = $this->mysql->dailySlices->getSlicesByMetric($metricId, $minute);
            $slicesAll = array_slice($slicesAll, 0, 10);
            $slicesIntersect = $intersect->getIntersect($slicesAll, 10);
            $value = mt_rand(100, 10000);
            $this->mysql->dailySliceIntersect10->createBatchSlices($metricId, $slicesIntersect, $value, $minute);
        }
    }
}