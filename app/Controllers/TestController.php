<?php


namespace App\Controllers;


use App\Biz\Event;

class TestController extends AbstractController
{
    public function server()
    {
        $event = new Event();

//        while (1) {
//            $event->save('Product.Some.Metric' . random_int(1, 100), random_int(23, 24322) / 1000, 'now');
//        }
        $events = [
            [
                'metric' => 'Product.Some.Metric',
                'value' => 1.0,
                'time' => 'now',
                'slices' => [
                    'vendor' => 'Gmail',
                    'site' => 'example.com'
                ]
            ],
            [
                'metric' => 'Product.Some.Metric',
                'value' => 1.0,
                'time' => 'now',
                'slices' => [
                    'vendor' => 'Yahoo',
                    'site' => 'example2.com'
                ]
            ],
            [
                'metric' => 'Product.Some.Metric',
                'value' => 1.0,
                'time' => 'now',
                'slices' => [
                    'vendor' => 'Gmail',
                    'site' => 'example.com'
                ]
            ],
            [
                'metric' => 'Product.Some.Metric',
                'value' => 1.0,
                'time' => 'now',
                'slices' => [
                    'vendor' => 'Yahoo',
                    'site' => 'example2.com'
                ]
            ],
        ];


        $added = $event->saveBatch($events);

        echo $added;
        die;
    }
}