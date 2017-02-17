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

        $capsule = new Illuminate\Database\Capsule\Manager();
        $capsule->setFetchMode(\PDO::FETCH_ASSOC);
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $builder = $capsule::connection();
        return new \App\Models\ModelFactory($builder);
    }
];