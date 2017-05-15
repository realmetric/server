<?php return [
    // Metrics on the start
    ['GET', '/metrics', 'App\Controllers\MetricsController::getAll'],

    // Metrics by metric id
    ['GET', '/metrics/{metric_id:\d+}', 'App\Controllers\MetricsController::getByMetricId'],

    // Slices by metric id
    ['GET', '/slices/{metric_id:\d+}', 'App\Controllers\SlicesController::getByMetricId'],

    // Tracking
    ['POST', '/track', 'App\Controllers\TrackController::create'],

    // Show values
    ['GET', '/values/minutes', 'App\Controllers\ValuesController::minutes'],
    ['GET', '/values/days', 'App\Controllers\ValuesController::days'],

    // Show totals, diffs
    ['GET', '/totals/minutes', 'App\Controllers\TotalsController::minutes'],
    ['GET', '/totals/days', 'App\Controllers\TotalsController::days'],
];