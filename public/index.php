<?php
require __DIR__ . '../../vendor/autoload.php';

if (is_readable(__DIR__ . '/../config/production/env.php')) {
    $config = require __DIR__ . '/../config/production/env.php';
} else {
    $config = require __DIR__ . '/../config/dev/env.php';
}
$dependencies = require __DIR__ . '/../app/dependencies.php';
$middleware = require __DIR__ . '/../app/middleware.php';
$routes = require __DIR__ . '/../app/routes.php';

$app = new \App\App();
$app->init($config, $dependencies);
$app->runHttp($middleware, $routes);