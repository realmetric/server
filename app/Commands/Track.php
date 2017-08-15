<?php
declare(strict_types = 1);

namespace App\Commands;

use App\Events\Pack;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Track extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $added = 0;

        do {
            $res = (int)$this->pack();
            $added += $res;

            $time = microtime(true) - $startTime;
            if ($time > 50) {
                $this->out('Not enough time. ' . $this->redis->track_raw->sCard() . ' left');
                break;
            }
        } while ($res || $time < 30);
        $this->out('Packed: ' . $added);

        $saved = $this->flush();
        $this->out("Saved: $saved");
    }

    private function flush()
    {
        $packer = new Pack();
        $count = $packer->flushMetrics();
//        $packer->flushSlices();
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

//        $minute = (int)(date('H') * 60 + date('i'));
        foreach ($rawEvents as $data) {
//            $ts = strtotime($date);
//            $minute = date('H', $ts) * 60 + date('i', $ts);

            // Force current minute
            $value = (int)$data['value'];
            $this->redis->track_aggr_metrics->zIncrBy($data['metric'], $value);

            foreach ($data['slices'] as $category => $slice) {
                $slicesKey = implode('|', [$data['metric'], $category, $slice]);
                $this->redis->track_aggr_metrics->zIncrBy($slicesKey, $value);
            }
        }
        return count($rawEvents);
    }
}