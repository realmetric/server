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
            'options' => array(
                PDO::ATTR_PERSISTENT => false,
            ),
        );

        $capsule = new Illuminate\Database\Capsule\Manager();
        $capsule->setFetchMode(\PDO::FETCH_ASSOC);
        $capsule->addConnection($config);
        $connection = $capsule->getConnection();
        return new \App\Model\ModelFactory($connection);
    },

    'redis' => function () {
        $redis = new \Redis();
        $redis->pconnect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
        $redis->select(getenv('REDIS_DBINDEX'));
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        $redis->setOption(\Redis::OPT_PREFIX, getenv('REDIS_PREFIX'));
        return new \App\Redis\KeyLocator($redis);
    }
];