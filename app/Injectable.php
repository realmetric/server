<?php

namespace App;

/**
 * @property \App\Models\ModelFactory mysql
 * @property Timer timer
 */

trait Injectable
{
    public function __get($name)
    {
        $container = App::getContainer();
        if ($container && $container->has($name)) {
            return $container->get($name);
        }
    }
}