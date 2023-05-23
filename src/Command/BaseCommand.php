<?php


namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        // Adding some sleep for supervisor auto restart
        $timeStart = microtime(true);
        try {
            $this->handle();
        } catch (\Throwable $exception) {
            if ($_ENV["APP_ENV"] != 'dev' && microtime(true) - $timeStart < 2) {
                sleep(2);
            }
            throw $exception;
        }
        if ($_ENV["APP_ENV"] != 'dev' && microtime(true) - $timeStart < 2) {
            sleep(2);
        }

        return 0;
    }

    abstract function handle();
}
