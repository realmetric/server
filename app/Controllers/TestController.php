<?php


namespace App\Controllers;


class TestController extends AbstractController
{
    public function server()
    {
        $mysql = $this->mysql->day->getById(1);

        var_dump($mysql);
        die;
    }
}