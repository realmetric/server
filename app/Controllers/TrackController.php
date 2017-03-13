<?php

namespace App\Controllers;

use App\Biz\Event;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        $eventService = new Event();
        $added = $eventService->saveBatch($data['events'], $data['metrics'], $data['categories'], $data['names']);

        return $this->jsonResponse(['createdEvents' => $added]);
    }
}