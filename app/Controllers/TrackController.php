<?php

namespace App\Controllers;

use App\Keys;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create(ServerRequestInterface $request)
    {
        $added = (int)$this->redis->sAdd(Keys::REDIS_SET_TRACK_QUEUE, file_get_contents('php://input'));
        return $this->jsonResponse(['createdEvents' => $added]);
    }
}