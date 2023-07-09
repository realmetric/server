<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Events\Dispatcher;
use PDO;

class ConnectionFactory
{
    public static function build(string $host, string $database, string $user, string $password, string $driver = 'mysql', int $port = 3306): Connection
    {
        $config = [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $user,
            'password' => $password,
            'charset' => 'utf8mb4',
            'prefix' => '',
            'options' => [
                PDO::ATTR_PERSISTENT => false,
            ],
        ];

        $capsule = new Manager();
        $dispatcher = new Dispatcher();
        $capsule->setEventDispatcher($dispatcher);
        $dispatcher->listen(StatementPrepared::class, function ($event) {
            $event->statement->setFetchMode(PDO::FETCH_ASSOC);
        });

        $capsule->addConnection($config);
        return $capsule->getConnection();
    }
}
