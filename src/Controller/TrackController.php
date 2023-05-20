<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TrackController extends AbstractController
{
    #[Route('/track', methods: ['POST'])]
    public function create()
    {
        $data = gzuncompress(file_get_contents('php://input'));
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->json(['createdEvents' => $added]);
    }

    #[Route('/trackOne', methods: ['POST'])]
    public function createOne()
    {
        $data = file_get_contents('php://input');
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->json(['createdEvents' => $added]);
    }

    #[Route('/track/testdata', methods: ['GET'])]
    public function createTest()
    {
        $data = json_encode([[
            'metric' => 'test',
            'value' => '3',
            'time' => time(),
            'slices' => [
                'some' => 'val',
                'other' => 12
            ],
        ]]);
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->json(['createdEvents' => $added]);
    }
}
