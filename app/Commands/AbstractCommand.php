<?php

namespace App\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    use \App\Injectable;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(PHP_EOL . 'Start at ' . date('Y-m-d H:i:s'));
    }
}