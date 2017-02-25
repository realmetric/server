<?php return [
    'mysql' => function () {
        $config = array(
            'driver' => 'mysql', // Db driver
            'host' => getenv('MYSQL_HOST'),
            'database' => getenv('MYSQL_DATABASE'),
            'username' => getenv('MYSQL_USERNAME'),
            'password' => getenv('MYSQL_PASSWORD'),
            'charset' => 'utf8', // Optional
            'collation' => 'utf8_unicode_ci', // Optional
            'prefix' => '', // Table prefix, optional
        );

        $capsule = new Illuminate\Database\Capsule\Manager();
        $capsule->setFetchMode(\PDO::FETCH_ASSOC);
        $capsule->addConnection($config);
        $builder = $capsule->getConnection();
        return new \App\Models\ModelFactory($builder);
    },

    'timer' => function () {
        return new \App\Timer();
    }
];