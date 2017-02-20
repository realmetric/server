<?php return [
    ['GET', '/', 'App\Controllers\TestController::server'],
    //-------------------

    // Metrics on the start
    ['GET', '/metrics', 'App\Controllers\MetricsController::getAll'],

    // Tracking
    ['POST', '/track', 'App\Controllers\TrackController::create'],

    // Show slice values by day
    ['GET', '/values/day', 'App\Controllers\ValuesDayController::get'],
];