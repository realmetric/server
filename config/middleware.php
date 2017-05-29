<?php return [
    new \App\Http\Middleware\TimerMiddleware(),

    // CORS
    function ($request, $delegate) {
        /* @var \Psr\Http\Message\ResponseInterface $response */
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \GuzzleHttp\Psr7\Response();
        } else {
            $response = $delegate->process($request);
        }

        $headers = [
            'Origin',
            'X-Requested-With',
            'Content-Range',
            'Content-Disposition',
            'Content-Type',
            'Authorization',
            'Accept',
            'Client-Security-Token',
            'X-CSRFToken',
        ];

        $method = [
            'POST',
            'GET',
            'OPTIONS',
            'DELETE',
            'PUT'
        ];

        $response = $response
            ->withHeader("Access-Control-Allow-Origin", '*')
            ->withHeader("Access-Control-Allow-Methods", implode(',', $method))
            ->withHeader("Access-Control-Allow-Headers", implode(',', $headers))
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader("Access-Control-Allow-Credentials", 'true');

        return $response;
    },


    // Error handler
    function ($request, $delegate) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
        $whoops->register();

        return $delegate->process($request);
    },

    new FastRouteMiddleware(require __DIR__ . '/routes.php', '\App\Http\Controllers\NotFoundController::showMessage'),
    new \App\Http\Middleware\RequestHandlerMiddleware(),
];