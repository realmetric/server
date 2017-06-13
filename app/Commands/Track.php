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
            if (microtime(true) - $startTime > 59) {
                $this->output->writeln('Not enough time. ' . $this->redis->track_raw->sCard() . ' left');
                break;
            }
        } while ($res);
        $this->output->writeln('Packed: ' . $added);

        $saved = $this->flush();
        $this->output->writeln("Saved: $saved");
    }

    private function flush()
    {
        $packer = new Pack();
        $count = $packer->flushMetrics();
        $packer->flushSlices();
        return $count;
    }

    private function pack()
    {
        $packer = new Pack();
        $eventPack = $this->redis->track_raw->sPop();
        if (!$eventPack) {
            return 0;
        }
        $rawEvents = json_decode(gzuncompress($eventPack), true);

        if (!count($rawEvents) || !count($rawEvents[0])) {
            return 0;
        }

        foreach ($rawEvents as $data) {
//            $ts = strtotime($date);
//            $minute = date('H', $ts) * 60 + date('i', $ts);

            // Force current minute
            $minute = (int)(date('H') * 60 + date('i'));
            $value = (int)$data['value'];
            $packer->addMetric($data['metric'], $minute, $value);

            if (!isset($data['slices'])) {
                continue;
            }
            foreach ($data['slices'] as $category => $slice) {
                $packer->addSlice($data['metric'], $category, $slice, $minute, $value);
            }
        }
        return count($rawEvents);
    }
}