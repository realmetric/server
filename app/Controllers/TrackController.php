<?php

namespace App\Controllers;

use App\Biz\Event;
use App\Keys;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create()
    {
        $data = gzuncompress(file_get_contents('php://input'));
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->jsonResponse(['createdEvents' => $added]);
    }
    
    public function createOne()
    {
        $data = file_get_contents('php://input');
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->jsonResponse(['createdEvents' => $added]);
    }

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
        return $this->jsonResponse(['createdEvents' => $added]);
    }
}
