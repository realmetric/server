<?php

namespace App;

use Interop\Container\ContainerInterface;
use League\Container\Container;
use Middleland\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;

class App
{
    public function run(array $config, array $dependencies, array $middleware, array $routes)
    {
        // Env
        $this->loadEnv($config);

        // Container
        $container = $this->buildContainer($dependencies);

        // Request
        $request = $this->getRequest();

        // Router
        $router = $this->routerMiddleware($routes, $container);

        // Middleware
        $middleware = array_merge($middleware, [$router]);
        $dispatcher = new Dispatcher($middleware, $container);
        $response = $dispatcher->dispatch($request);

        // Response
        $this->sendGlobalResponse($response);
    }

    private function getRequest()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $parsedBody = array_merge($_POST, $input);
        return ServerRequestFactory::fromGlobals(null, null, $parsedBody);
    }

    private function sendGlobalResponse(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        $protocolVersion = $response->getProtocolVersion();
        header("HTTP/{$protocolVersion} $statusCode $reasonPhrase");
        foreach ($response->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $cookie) {
                    header(sprintf('Set-Cookie: %s', $cookie), false);
                }
                break;
            }
            header(sprintf('%s: %s', $name, $response->getHeaderLine($name)));
        }
        $body = $response->getBody();
        if ($body) {
            echo $body->__toString();
        }
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