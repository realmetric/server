<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager;

class ConnectionFactory
{
    public static function build(string $host, string $database, string $user, string $password)
    {
        $config = [
            'driver' => 'mysql',
            'host' => $host,
            'database' => $database,
            'username' => $user,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_PERSISTENT => false,
            ],
        ];

        $capsule = new Manager();
        $capsule->setFetchMode(\PDO::FETCH_ASSOC);
        $capsule->addConnection($config);
        return $capsule->getConnection();
    }
}