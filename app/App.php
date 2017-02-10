<?php

namespace App;

use Interop\Container\ContainerInterface;
use League\Container\Container;
use Middleland\Dispatcher;
use Zend\Diactoros\ServerRequestFactory;

class App
{
    public function run(array $config, array $dependencies, array $middleware, array $routes)
    {
        // Mute any out
        ob_start();

        // Env
        $this->loadEnv($config);

        // Container
        $container = $this->buildContainer($dependencies);

        // Request
        $request = ServerRequestFactory::fromGlobals();

        // Router
        $router = $this->routerMiddleware($routes, $container);

        // Middleware
        $middleware = array_merge($middleware, [$router]);
        $dispatcher = new Dispatcher($middleware, $container);
        $response = $dispatcher->dispatch($request);

        // Tech out
        $techOut = ob_get_clean();

        // Response
        echo $response->getBody()->getContents();
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

    private function routerMiddleware($routes, ContainerInterface $container)
    {
        $dispatcher = \Fastroute\simpleDispatcher(function (\FastRoute\RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });
        return (new \Middlewares\FastRoute($dispatcher))->arguments($container);
    }
}