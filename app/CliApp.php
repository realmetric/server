<?php

namespace App;

use Interop\Container\ContainerInterface;
use League\Container\Container;
use Middleland\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Application;
use Zend\Diactoros\ServerRequestFactory;

class CliApp extends Application
{
    private static $container;

    public function __construct(array $config, array $dependencies)
    {
        parent::__construct($name = 'UNKNOWN', $version = 'UNKNOWN');
        // Env
        $this->loadEnv($config);

        // Container
        self::$container = $this->buildContainer($dependencies);
    }

    public static function getContainer()
    {
        return self::$container;
    }

    private function loadEnv(array $config)
    {
        $hasPutenv = function_exists('putenv');
        foreach ($config as $name => $value) {
            if ($hasPutenv) {
                putenv($name . '=' . $value);
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    private function buildContainer($dependencies)
    {
        $container = new Container();
        foreach ($dependencies as $name => $callable) {
            $container->share($name, $callable);
        }
        return $container;
    }
}