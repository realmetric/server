<?php

namespace App;

use Interop\Container\ContainerInterface;

/**
 * @property ContainerInterface container
 */
class Injectable
{
    private $container = false;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __get($name)
    {
        if ($this->container && $this->container->has($name)) {
            return $this->container->get($name);
        }
    }
}