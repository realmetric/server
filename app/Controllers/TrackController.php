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

        foreach ($events as $event) {
            $metric = $event['metric'];
            $value = (float) $event['value'] ?? 1;
            $time = isset($event['time']) ? strtotime($event['time']) : time();
            $slices = $event['slices'] ?? null;

            $event = new Event();
            $event->save($metric, $value, $time, $slices);
        }

        return $this->jsonResponse(true);
    }
}