<?php

namespace App\Commands\Daily;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggrSlices extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timeStart = time();
            $saved = $this->flush($timeStart);
            $this->out("Aggregated {$saved} daily slices");
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    protected function flush($timestamp)
    {
        $totalsAggrStmt = $this->mysql->dailySlices->aggregate($timestamp);
        return $totalsAggrStmt->rowCount();
    }

}