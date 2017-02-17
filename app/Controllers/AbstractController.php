<?php

namespace App\Controllers;

use Lukasoppermann\Httpstatus\Httpstatuscodes;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractController
{
    use \App\Injectable;

    public function jsonResponse($data, $status = Httpstatuscodes::HTTP_OK)
    {
        return new JsonResponse($data, $status);
    }
}