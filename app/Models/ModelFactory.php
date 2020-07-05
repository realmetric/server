<?php


namespace App\Models;

/**
 * @property MetricsModel metrics
 * @property SlicesModel slices
 * @property DailyMetricsModel dailyMetrics
 * @property DailySlicesModel dailySlices
 * @property DailySliceIntersect10Model dailySliceIntersect10
 * @property DailySliceTotalsModel dailySliceTotals
 * @property DailyRawMetricsModel dailyRawMetrics
 * @property DailyRawSlicesModel dailyRawSlices
 * @property DailyCountersModel dailyCounters
 * @property MonthlyMetricsModel monthlyMetrics
 * @property MonthlySlicesModel monthlySlices
 * @property CountersModel counters
 * @property DailyMetricTotalsModel dailyMetricTotals
 */

class ModelFactory
{
    protected $cache = [];
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
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
        $this->cache[$name] = new $className($this->connection);
        return $this->cache[$name];
    }

    public function getConnection()
    {
        return $this->connection;
    }
}