<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    private $output;
    private $lastOut;

    use \App\Injectable;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->out('Started');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $timeStart = microtime(true);
        try {
            parent::run($input, $output);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage() . ' ' . json_encode(debug_backtrace()));
        }
        $output->writeln('Done in ' . number_format(microtime(true) - $timeStart, 3));
    }

    public function out($message)
    {
        if (!$this->lastOut) {
            $this->lastOut = time();
        }
        $fromLast = time() - $this->lastOut;
        if ($fromLast) {
            $fromLast = " +{$fromLast}s";
        } else {
            $fromLast = '';
        }
        $this->output->writeln(date('[m.d H:i') . $fromLast . '] [' . $this->getName() . '] ' . $message);
        $this->lastOut = time();
    }
}