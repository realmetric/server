<?php

namespace App\Controller;

use App\Library\EventSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TrackController extends AbstractController
{
    public function __construct(
        private readonly EventSaver $eventSaver,
    )
    {
    }

    #[Route('/track', methods: ['POST'])]
    public function create()
    {
        $data = file_get_contents('php://input');
        $events = json_decode($data, true);

        foreach ($events as $event) {
            $this->eventSaver->save(
                $event['category'] . '.' . $event['event'],
                $event['value'],
                $event['timestamp'],
                $event['segments']
            );
        }
        return $this->json(['createdEvents' => count($events)]);
    }
}
