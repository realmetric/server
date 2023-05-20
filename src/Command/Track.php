<?php
declare(strict_types = 1);

namespace App\Command;

use App\Library\Pack;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Track extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true){
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
        $startTime = microtime(true);
        $added = 0;

        do {
            $res = (int)$this->pack();
            $added += $res;

            $time = microtime(true) - $startTime;
            if ($time > 60) {
                $cnt = $this->redis->track_raw->sCard();
                if ($cnt){
                    $this->out('Not enough time. ' . $cnt . ' left');
                    $this->redis->track_raw->del();
                    $this->out('[!] Deleted track_raw from redis');
                }
            }
        } while ($time < 60);
        $this->out('Packed: ' . $added);

        $saved = $this->flush();
        $this->out("Saved: $saved");
    }

    private function flush()
    {
        $packer = new Pack();
        $count = $packer->flushMetrics();
        $slicesCount = $packer->flushSlices();
        $this->out('Slices count: '.$slicesCount);
        return $count;
    }

    private function pack()
    {
        $eventPack = $this->redis->track_raw->sPop();
        if (!$eventPack) {
            return 0;
        }
        $rawEvents = json_decode($eventPack, true);

        if (!count($rawEvents) || !count($rawEvents[0])) {
            return 0;
        }

        $pipe = $this->redis->getPipe();

//        $minute = (int)(date('H') * 60 + date('i'));
        foreach ($rawEvents as $data) {
//            $ts = strtotime($date);
//            $minute = date('H', $ts) * 60 + date('i', $ts);

            // Force current minute
            $value = (int)$data['value'];
//            $this->redis->track_aggr_metrics->zIncrBy($data['metric'], $value);
            $pipe->zIncrBy('track_aggr_metrics', $value, $data['metric']);
            $pipe->zIncrBy('track_aggr_metric_totals', $value, $data['metric']);

            foreach ($data['slices'] as $category => $slice) {
                $slicesKey = implode('|', [$data['metric'], $category, $slice]);
//                $this->redis->track_aggr_slices->zIncrBy($slicesKey, $value);
                $pipe->zIncrBy('track_aggr_slices', $value, $slicesKey);
                $pipe->zIncrBy('track_aggr_slice_totals', $value, $slicesKey);
            }
        }
        if ($rawEvents){
            $pipe->exec();
        }

        return count($rawEvents);
    }
}