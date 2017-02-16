<?php


namespace App\Controllers;


class TestController extends AbstractController
{
    public function server()
    {
        $this->mysql->day->create(1, 2, 'asda');

//        var_dump($_SERVER);
        die;
    }
}