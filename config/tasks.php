<?php return [
    new App\Commands\Clean\Raw('clean:raw'),
    new App\Commands\Clean\Old('clean:old'),

    new App\Commands\Raw\Metrics('raw:metrics'),
    new App\Commands\Raw\Slices('raw:slices'),
    new App\Commands\Raw\Track('raw:track'),
    new App\Commands\Daily\Metrics('daily:metrics'),
    new App\Commands\Daily\Slices('daily:slices'),
    new App\Commands\Daily\SliceIntersect('daily:slice_intersect'),
    new App\Commands\Daily\AggrSlices('daily:aggr_slices'),
    new App\Commands\Daily\AggrMetrics('daily:aggr_metrics'),

    new App\Commands\Track('track'),
    new App\Commands\FlushTotals('flush_totals'),
];