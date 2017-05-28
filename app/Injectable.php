<?php

namespace App;

/**
 * @property \App\Models\ModelFactory mysql
 * @property Timer timer
 * @property \Redis redis
 */

trait Injectable
{
    use \Injectable\Injectable;
}