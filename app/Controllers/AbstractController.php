<?php

namespace App\Controllers;

use Lukasoppermann\Httpstatus\Httpstatuscodes;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractController
{
    use \App\Injectable;

    public function jsonResponse(array $data, $status = Httpstatuscodes::HTTP_OK)
    {
        $this->timer->endPoint('APP');
        $data['_timing'] = $this->timer->getResults();
        return new JsonResponse($data, $status);
    }
}