<?php


namespace App\Controllers;


class TestController extends BaseController
{
    public function server()
    {
        $mysql = $this->mysql;

        var_dump($mysql);
        die;
    }
}