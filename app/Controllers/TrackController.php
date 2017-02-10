<?php

namespace App\Controllers;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends BaseController
{
    public function create(ServerRequestInterface $request, ContainerInterface $di)
    {
        $records = $request->getParsedBody();
        if (!$records || count($records)) {
            throw new \Exception('shit!');
        }
//        $id = $request->getAttribute('id', 123);
//
//        $my = $di->get('mysql');
//        var_dump($my);

//        return $this->jsonResponse($body);
    }
}