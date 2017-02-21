<?php

namespace App\Commands;

use App\CliApp;
/**
 * @property \App\Models\ModelFactory mysql
 */
abstract class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    public function __get($name)
    {
        $container = CliApp::getContainer();
        if ($container && $container->has($name)) {
            return $container->get($name);
        }
    }
}