<?php

namespace App;

/**
 * @property \App\Models\ModelFactory mysql
 * @property \App\Performance\Timer timer
 * @property \App\Redis\KeyLocator redis
 */

trait Injectable
{
    use \Injectable\BaseInjectable;
}