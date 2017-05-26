<?php return [
    // Metrics
    ['GET', '/metrics', 'App\Controllers\MetricsController::getAll'],
    ['GET', '/metrics/slice/{slice_id:\d+}', 'App\Controllers\MetricsController::getAll'],
    ['GET', '/metrics/{metric_id:\d+}', 'App\Controllers\MetricsController::getByMetricId'],

    // Slices
    ['GET', '/slices', 'App\Controllers\SlicesController::getAll'],
    ['GET', '/slices/{metric_id:\d+}', 'App\Controllers\SlicesController::getByMetricId'],

    // Track new data
    ['GET', '/track/testdata', 'App\Controllers\TrackController::createTest'],
    ['POST', '/track', 'App\Controllers\TrackController::create'],

    // Values for graph
    ['GET', '/values/minutes', 'App\Controllers\ValuesController::minutes'],
    ['GET', '/values/days', 'App\Controllers\ValuesController::days'],
];