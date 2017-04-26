<?php return [
    new App\Commands\Clean\Raw('clean:raw'),
    new App\Commands\Clean\Old('clean:old'),

    new App\Commands\Raw\Metrics('raw:metrics'),
    new App\Commands\Raw\Slices('raw:slices'),
    new App\Commands\Raw\Track('raw:track'),
];