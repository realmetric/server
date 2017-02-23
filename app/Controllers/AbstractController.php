<?php

namespace App\Controllers;

use Lukasoppermann\Httpstatus\Httpstatuscodes;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractController
{
    use \App\Injectable;

    public function jsonResponse(array $data, $status = Httpstatuscodes::HTTP_OK)
    {
        $data['appTime'] = floor((microtime(true) - APP_START_TIME) * 1000);
        return new JsonResponse($data, $status);
    }
}