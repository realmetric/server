<?php

namespace App\Controllers;

use App\Biz\Event;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create(ServerRequestInterface $request)
    {
        $events = $request->getParsedBody();
        if (!$events || !count($events)) {
            throw new \Exception('No events');
        }

        $eventService = new Event();
        $added = $eventService->saveBatch($events);

        return $this->jsonResponse(['createdEvents' => $added]);
    }
}