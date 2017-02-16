<?php


namespace App\Models;

/**
 * @property DailyMetricsModel dailyMetrics
 * @property DailySlicesModel dailySlices
 */

class ModelFactory
{
    protected $cache = [];
    protected $queryBuilder = null;

    public function __construct($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return AbstractModel
     */
    public function __get($name)
    {
        // Caching
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $className = '\App\Models\\' . ucfirst($name) . 'Model';
        if (!class_exists($className)) {
            throw new \Exception("Model {$name} not exist");
        }
        $this->cache[$name] = new $className($this->queryBuilder);
        return $this->cache[$name];
    }
}