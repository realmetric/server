<?php

namespace App\Command\Clean;

use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Old extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = date('Y_m_d', strtotime('-7 days'));
        $this->dropDate($date);
    }

    private function dropDate($date)
    {
        $this->out('Try drop ' . $date);
        $this->mysql->dailyMetrics->dropTable($date);
        $this->mysql->dailySlices->dropTable($date);
        $this->mysql->dailySliceTotals->dropTable($date);
        $this->out('Done');
    }
}