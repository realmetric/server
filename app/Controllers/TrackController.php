<?php

namespace App\Controllers;

use App\Biz\Event;
use App\Keys;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create()
    {
        $added = (int)$this->redis->sAdd(Keys::REDIS_SET_TRACK_QUEUE, file_get_contents('php://input'));
        return $this->jsonResponse(['createdEvents' => $added]);
    }

    public function createTest()
    {
        $eventService = new Event();
        $id = $eventService->save('Test', 1, time(), ['some' => 'val', 'other' => 12]);
        return $this->jsonResponse([$id]);
    }
}