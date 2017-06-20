<?php

namespace App\Commands\Clean;

use App\Commands\AbstractCommand;
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
        $this->out('Done');
    }
}