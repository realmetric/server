<?php


namespace App\Redis;

/**
 * @property Set track_raw
 * @property SortedSet track_aggr_metrics
 * @property SortedSet track_aggr_slices
 * @property SortedSet track_aggr_metric_totals
 * @property SortedSet track_aggr_slice_totals
 * @property Str flush_totals_time
 * @property Str flush_totals_reset_time
 */

class KeyLocator
{
    private $connection;
    private $keys = [
        'flush_totals_time' => 'Str',
        'flush_totals_reset_time' => 'Str',
        'track_raw' => 'Set',
        'track_aggr_metrics' => 'SortedSet',
        'track_aggr_slices' => 'SortedSet',
        'track_aggr_metric_totals' => 'SortedSet',
        'track_aggr_slice_totals' => 'SortedSet',
    ];

    public function __construct(\Redis $connection)
    {
        $this->connection = $connection;
    }

    public function __get($name)
    {
        if (isset($this->keys[$name])) {
            $class = '\App\Redis\\' . $this->keys[$name];
            return new $class($name, $this->connection);
        }
    }

    public function getPipe()
    {
        return $this->connection->multi(\Redis::PIPELINE);
    }
}