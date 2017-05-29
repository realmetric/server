<?php


namespace App\Http\Middleware;

use App\Injectable;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class TimerMiddleware implements MiddlewareInterface
{
    use Injectable;

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->timer->startPoint('middleware');
        return $delegate->process($request);
    }
}