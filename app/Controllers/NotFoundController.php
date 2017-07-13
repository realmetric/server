<?php

namespace App\Controllers;

use GuzzleHttp\Psr7\Response;

class NotFoundController extends AbstractController
{
    public function showMessage()
    {
        return new Response(404);
    }
}