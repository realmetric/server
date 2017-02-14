<?php

namespace App;

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