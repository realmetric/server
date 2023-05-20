<?php return [

    'redis' => function () {
        $redis = new \Redis();
        $redis->pconnect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
        $redis->select(getenv('REDIS_DBINDEX'));
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        $redis->setOption(\Redis::OPT_PREFIX, getenv('REDIS_PREFIX'));
        return new \App\Redis\KeyLocator($redis);
    }
];