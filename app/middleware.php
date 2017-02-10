<?php return [

    // Error handler
    function ($request, $delegate) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
        $whoops->register();

        return $delegate->process($request);
    },
];