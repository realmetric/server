<?php
require __DIR__ . '../../app/Timer.php';
\App\Timer::startPointStatic('TOTAL');

\App\Timer::startPointStatic('init');
require __DIR__ . '../../vendor/autoload.php';

$config = require __DIR__ . '/../config/env.php';
$dependencies = require __DIR__ . '/../app/dependencies.php';
$middleware = require __DIR__ . '/../app/middleware.php';
$routes = require __DIR__ . '/../app/routes.php';

// Config to ENV
foreach ($config as $name => $value) {
    putenv($name . '=' . $value);
}

// Services container
$container = \Injectable\Factories\LeagueFactory::fromConfig($dependencies);
\Injectable\ContainerSingleton::setContainer($container);

\App\Timer::endPointStatic('init');

// Process HTTP
\App\Timer::startPointStatic('middleware');
$http = new \App\Http();
$http->runHttp($middleware, $routes);