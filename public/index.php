<?php
require __DIR__ . '../../app/Timer.php';
\App\Timer::startPointStatic('TOTAL');

\App\Timer::startPointStatic('init');
require __DIR__ . '../../vendor/autoload.php';

$config = require __DIR__ . '/../config/env.php';
$services = require __DIR__ . '/../config/services.php';
$middleware = require __DIR__ . '/../config/middleware.php';
$routes = require __DIR__ . '/../config/routes.php';

// Config to ENV
foreach ($config as $name => $value) {
    putenv($name . '=' . $value);
}

// Services container
$container = \Injectable\Factories\LeagueFactory::fromConfig($services);
\Injectable\ContainerSingleton::setContainer($container);
\App\Timer::endPointStatic('init');

// Process HTTP
\App\Timer::startPointStatic('middleware');
$http = new \App\Http();
$http->runHttp($middleware, $routes);