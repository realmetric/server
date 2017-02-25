<?php
require __DIR__ . '../../app/Timer.php';
\App\Timer::startPointStatic('TOTAL');

\App\Timer::startPointStatic('init');
require __DIR__ . '../../vendor/autoload.php';

$config = require __DIR__ . '/../config/env.php';
$dependencies = require __DIR__ . '/../app/dependencies.php';
$middleware = require __DIR__ . '/../app/middleware.php';
$routes = require __DIR__ . '/../app/routes.php';

$app = new \App\App();
$app->init($config, $dependencies);
\App\Timer::endPointStatic('init');
\App\Timer::startPointStatic('middleware');
$app->runHttp($middleware, $routes);