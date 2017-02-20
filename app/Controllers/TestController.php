<?php


namespace App\Controllers;


use App\Biz\Event;

class TestController extends AbstractController
{
    public function server()
    {
        $event = new Event();

        while (1) {
            $event->save('Product.Some.Metric' . random_int(1, 100), random_int(23, 24322) / 1000, 'now');
        }


//        var_dump($_SERVER);
        die;
    }
}