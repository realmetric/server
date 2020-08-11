<?php

namespace App;

/**
 * @property \App\Models\ModelFactory mysql
 * @property \App\Performance\Timer timer
 * @property \App\Redis\KeyLocator redis
 * @property \App\ElasticSearch\Model es
 */

trait Injectable
{
    use \Injectable\BaseInjectable;
}
