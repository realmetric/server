<?php

namespace App\Controllers;

use Lukasoppermann\Httpstatus\Httpstatuscodes;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractController
{
    use \App\Injectable;

    public function __construct()
    {
        $this->timer->endPoint('middleware');
        $this->timer->startPoint('controller');
    }

    public function jsonResponse(array $data, $status = Httpstatuscodes::HTTP_OK)
    {
        $this->timer->endPoint('controller');
        $this->timer->endPoint('TOTAL');
        $data['_timing'] = $this->timer->getResults();
        return new JsonResponse($data, $status);
    }
}