<?php

namespace App\Command;

use App\Library\EventSaver;
use App\Model\MetricsModel;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:self_check'
)]
class SelfCheckCommand extends BaseCommand
{
    public function __construct(
        private readonly MetricsModel $metrics,
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $error = false;
        try {
            $this->metrics->select()->getFirstRow();
        } catch (\Throwable $ex) {
            echo "Problem with database:\n" . $ex->getMessage();
            $error = true;
        }
        if ($error) {
            echo "Done with errors.\n";
        } else {
            echo "Done.\n";
        }
    }
}
