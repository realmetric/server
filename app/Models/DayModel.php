<?php

namespace App\Models;

class DayModel extends AbstractModel
{
    const TABLE = 'daily_metrics_2017-01-01'; // Just for example

    public function create(int $metricId, int $valueId, string $time):int
    {
        return $this->insert([
            'metric_id' => $metricId,
            'value_id' => $valueId,
            'created_at' => $time,
        ]);
    }
}