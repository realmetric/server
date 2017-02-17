<?php


namespace App\Controllers;


use App\Biz\Event;

class TestController extends AbstractController
{
    public function server()
    {
        $event = new Event();
        $event->save('Product.Some.Metric', random_int(23, 24322) / 100, 'now');

//        var_dump($_SERVER);
        die;
    }
}