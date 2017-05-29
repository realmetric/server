<?php


namespace App\Performance;

class Timer
{
    private $startPoints = [];
    private $endPoints = [];

    public function __construct()
    {
        if (defined('APP_START_TIME')) {
            $this->startPoints[] = ['init', null, APP_START_TIME];
            $this->endPoint('init');
        }
    }

    public function startPoint($name, $context = null)
    {
        $this->startPoints[] = [$name, $context, microtime(true)];
    }

    public function endPoint($name)
    {
        $this->endPoints[] = [$name, microtime(true)];
    }

    public function getResults()
    {
        $starts = $this->startPoints;

        $results = [];
        foreach ($this->endPoints as $endPoint) {
            $name = $endPoint[0];
            $endTime = $endPoint[1];

            $startTime = false;
            foreach ($starts as $id => $start) {
                if ($start[0] == $name) {
                    $startTime = $start[2];
                    unset($starts[$id]);
                    break;
                }
            }
            if (!$startTime) {
                continue;
            }

            $diff = number_format(($endTime - $startTime) * 1000, 2);
            $results[] = [$name, $diff];
        }

        return $results;
    }
}