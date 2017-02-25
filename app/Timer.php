<?php


namespace App;

class Timer
{
    private static $startPointsStatic = [];
    private static $endPointsStatic = [];
    private $startPoints = [];
    private $endPoints = [];

    public function __construct()
    {
        $this->startPoints = self::$startPointsStatic;
        $this->endPoints = self::$endPointsStatic;
    }

    public static function startPointStatic($name, $context = null)
    {
        self::$startPointsStatic[] = [$name, $context, microtime(true)];
    }

    public static function endPointStatic($name)
    {
        self::$endPointsStatic[] = [$name, microtime(true)];
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
        $names = array_column($this->startPoints, 0);

        $results = [];
        foreach ($this->endPoints as $endPoint) {
            $name = $endPoint[0];
            $endTime = $endPoint[1];
            $id = array_search($name, $names);
            if ($id === false) {
                continue;
            }

            $startTime = $this->startPoints[$id][2];

            $diff = number_format(($endTime - $startTime) * 1000, 2);
            $results[] = [$name, $diff];
        }

        return $results;
    }
}