<?php
declare(strict_types=1);

namespace App\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlushTotals extends AbstractCommand
{
    private $refillTotalsFromDb = true;

    protected function configure()
    {
        $this->addOption('no-refill', null,InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('no-refill')){
            $this->refillTotalsFromDb = false;
        }

        while (true) {
            $timeStart = time();
            $this->process();
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    public function process()
    {
//        $this->op
        if ($this->refillTotalsFromDb) {
            $this->fillTotalsFromDb();
            $this->refillTotalsFromDb = false;
        }

        if ($this->timeToFlush()) {
            $this->flushTotals();
        } else {
            $this->out('Not time to flush');
        }

        if ($this->timeToRestart()) {
            $this->out('Time to restart');
            exit(0);
        }
    }

    protected function flushTotals()
    {
        $metricsCount = $this->flushMetricTotals();
        $this->out('Flushed ' . $metricsCount . ' metrics');
        $slicesCount = $this->flushSliceTotals();
        $this->out('Flushed ' . $slicesCount . ' slices');
    }

    protected function flushMetricTotals(): int
    {
        $metrics = $this->redis->track_aggr_metric_totals->getAll();
        if (!$metrics) {
            return 0;
        }

        $records = [];
        foreach ($metrics as $member => $value) {
            $value = (int)$value;

            // Find metric
            $metricId = $this->mysql->metrics->getId($member);

            $records[] = ['metric_id' => $metricId, 'value' => $value, 'diff' => 0];

        }
        $this->mysql->dailyMetricTotals->setTableFromTimestamp(time())->truncate();
        $this->mysql->dailyMetricTotals->setTableFromTimestamp(time())->insertBatch($records);
        return count($records);
    }

    protected function flushSliceTotals(): int
    {
        $slices = $this->redis->track_aggr_slice_totals->getAll();
        if (!$slices) {
            return 0;
        }

        $records = [];
        foreach ($slices as $member => $value) {
            $memberData = explode('|', $member);
            $metricName = $memberData[0];
            $category = $memberData[1];
            $sliceName = $memberData[2];
            $value = (int)$value;

            // Find metrics and slices
            $metricId = $this->mysql->metrics->getId($metricName);
            $sliceId = $this->mysql->slices->getId($category, $sliceName);

            $records[] = ['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value, 'diff' => 0];

        }
        $this->mysql->dailySliceTotals->setTableFromTimestamp(time())->truncate();
        $this->mysql->dailySliceTotals->setTableFromTimestamp(time())->insertBatch($records);
        return count($records);
    }

    protected function timeToFlush()
    {
        $timestamp = time();
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        if ($minute % 5 !== 0) {
            return false;
        }

        $lastFlushTime = $this->redis->flush_totals_time->get() ?? 0;
        if ($minute > $lastFlushTime) {
            $this->redis->flush_totals_time->set($lastFlushTime, 3600);
            return true;
        }

        return false;
    }

    protected function fillTotalsFromDb()
    {
        $metricsCount = $this->fillMetricsFromDb(time());
        $this->out('Filled ' . $metricsCount . ' metrics');
        $slicesCount = $this->fillSlicesFromDb(time());
        $this->out('Filled ' . $slicesCount . ' slices');
        $this->redis->flush_totals_reset_time->set(time());
    }

    protected function fillMetricsFromDb(int $time): int
    {
        $metrics = $this->mysql->dailyMetrics->setTableFromTimestamp($time)->getTotals($time, true);
        if (!$metrics) {
            return 0;
        }

        $this->redis->track_aggr_metric_totals->del();
        $pipe = $this->redis->getPipe();
        foreach ($metrics as $metric) {
            $pipe->zIncrBy('track_aggr_metric_totals', (int)$metric['value'], $metric['name']);
        }
        $pipe->exec();

        return count($metrics);
    }

    protected function fillSlicesFromDb(int $time): int
    {
        $slices = $this->mysql->dailySlices->setTableFromTimestamp($time)->getTotalsWithCategoryNames($time);
        if (!$slices) {
            return 0;
        }

        $this->redis->track_aggr_slice_totals->del();
        $pipe = $this->redis->getPipe();
        foreach ($slices as $slice) {
            $slicesKey = implode('|', [$slice['metric_name'], $slice['category'], $slice['name']]);
            $pipe->zIncrBy('track_aggr_slice_totals', (int)$slice['value'], $slicesKey);
        }
        $pipe->exec();
        return count($slices);
    }

    protected function timeToRestart()
    {
        $lastStart = $this->redis->flush_totals_reset_time->get();

        if (is_numeric($lastStart) && $lastStart < strtotime('today')) {
            $this->out($lastStart);
            return true;
        }

        return false;
    }
}