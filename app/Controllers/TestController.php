<?php


namespace App\Controllers;


class TestController extends AbstractController
{
    public function server()
    {
        $mysql = $this->mysql;

        var_dump($mysql);
        die;
    }
}