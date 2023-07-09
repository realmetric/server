<?php

namespace App\Command;

use App\Library\EventSaver;
use React\Datagram\Factory;
use React\Datagram\Socket;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'app:udp_server'
)]
class UdpServerCommand extends BaseCommand
{
    public function __construct(
        private readonly EventSaver $eventSaver,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('host', InputArgument::REQUIRED, 'Host, e.g. 127.0.0.1')
            ->addArgument('port', InputArgument::REQUIRED, 'Port, e.g. 8888');
    }

    public function handle()
    {
        $host = $this->input->getArgument('host');
        $port = $this->input->getArgument('port');
        echo "Started UDP server on $host:$port\n";
        (new Factory())->createServer("$host:$port")->then(function (Socket $server) {
            $server->on('message', function ($message, $address, $server) {
                try {
                    $events = json_decode($message, true);
                    foreach ($events as $event) {
                        $metric = $event['category'] . '.' . $event['event'];
                        $this->eventSaver->save($metric, $event['value'], $event['timestamp'], $event['segments'], true);
                    }

                    $this->eventSaver->flush(); // TODO once per minute at least
                } catch (\Throwable $ex) {
                    echo 'Error: ' . $ex->getMessage() . "\n";
                }
            });
        });
    }
}
