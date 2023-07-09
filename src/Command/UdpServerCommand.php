<?php

namespace App\Command;

use App\Library\EventSaver;
use Symfony\Component\Console\Attribute\AsCommand;

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

    public function handle()
    {
        $host = 'localhost';
        $port = 8888;
        echo "Started UDP server on $host:$port\n";
        (new \React\Datagram\Factory())->createServer("$host:$port")->then(function (\React\Datagram\Socket $server) {
            $server->on('message', function ($message, $address, $server) {
                $events = json_decode($message, true);
                foreach ($events as $event) {
                    try {
                        $metric = $event['category'] . '.' . $event['event'];
                        $this->eventSaver->save($metric, $event['value'], $event['timestamp'], $event['segments'], true);
                    } catch (\Exception $ex) {
                        echo 'Error' . $ex->getMessage() . "\n";
                    }
                }
            });
        });
    }
}
