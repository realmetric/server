<?php return [
    // CORS
    function ($request, $delegate) {
        /* @var \Zend\Diactoros\Response $response */
        if ($request->getMethod() === 'OPTIONS'){
            $response = new \Zend\Diactoros\Response();
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
];