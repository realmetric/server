<?php


namespace App\Controllers;


use App\Biz\Event;

class TestController extends AbstractController
{
    public function server()
    {
        $event = new Event();
        $event->save(1);

//        var_dump($_SERVER);
        die;
    }
}