<?php return [
    'mysql' => function () {
        $config = array(
            'driver' => 'mysql', // Db driver
            'host' => 'localhost',
            'database' => getenv('MYSQL_DATABASE'),
            'username' => getenv('MYSQL_USERNAME'),
            'password' => getenv('MYSQL_PASSWORD'),
            'charset' => 'utf8', // Optional
            'collation' => 'utf8_unicode_ci', // Optional
            'prefix' => '', // Table prefix, optional
            'options' => array( // PDO constructor options, optional
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ),
        );
//        $qb = new \Library\Db\PeerFactory($config);

//        $connection = new \Pixie\Connection('mysql', $config);
//        $this->qb = new \Pixie\QueryBuilder\QueryBuilderHandler($connection);
//        $className = '\App\Peers\\' . ucfirst($table);
//        $qb = $this->qb->table($table);
//        return new $className($qb);

//        return $qb;
        return $config;
    }
];