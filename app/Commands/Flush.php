<?php
declare(strict_types = 1);

namespace App\Commands;

use App\Events\Pack;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Flush extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $saved = $this->flush();
        $this->output->writeln("Saved: $saved[1] from $saved[0]");
    }

    private function flush()
    {
        $packer = new Pack();
        $count = $packer->flushMetrics();
        $packer->flushSlices();
        return $count;
    }
}