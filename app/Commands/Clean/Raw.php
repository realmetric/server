<?php

namespace App\Commands\Clean;

use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Raw extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $date = date('Y_m_d_H', strtotime('-2 hour'));
        $this->dropDate($date);

        $date = date('Y_m_d_H', strtotime('-3 hour'));
        $this->dropDate($date);
    }

    private function dropDate($date)
    {
        $this->out('Try drop ' . $date);
        $this->mysql->dailyRawMetrics->dropTable($date);
        $this->mysql->dailyRawSlices->dropTable($date);
        $this->mysql->dailyCounters->dropTable($date);
        $this->out('Done');
    }
}