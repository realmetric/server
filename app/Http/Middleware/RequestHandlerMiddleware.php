<?php

namespace App\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandlerMiddleware implements MiddlewareInterface
{
    const HANDLER_ATTRIBUTE = 'request-handler';

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $handler = $request->getAttribute(self::HANDLER_ATTRIBUTE);
        list($class, $method) = explode('::', $handler);
        $class = new $class;
        $result = call_user_func_array([$class, $method], [$request]);
        $body = json_encode($result);
        $status = 200;
        $headers = [];
        return new Response($status, $headers, $body);
    }
}