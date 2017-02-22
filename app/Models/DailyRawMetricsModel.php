<?php

namespace App\Models;

class DailyRawMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_raw_metrics_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($queryBuilder)
    {
        parent::__construct($queryBuilder);

        $this->setTable(self::TABLE_PREFIX . date('Y_m_d'));
        $this->createTable($this->getTable());
    }

    private function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedSmallInteger('metric_id');
            $table->float('value');
            $table->unsignedSmallInteger('minute');

            $table->index('metric_id');

        });
    }

    public function create(int $metricId, float $value, string $time): int
    {
        $ts = strtotime($time);
        $minutes = date('H', $ts) * 60 + date('i', $ts);
        return $this->insert([
            'metric_id' => $metricId,
            'value' => $value,
            'minute' => $minutes,
        ]);
    }

    public function getAllMetrics()
    {
        return $this->qb()->selectRaw('metric_id, sum(value) as value')
            ->groupBy('metric_id')
            ->get();
    }

    public function getAggregatedData(string $time = 'now', int $startId = 0): array
    {
        $ts = strtotime($time);
        $minutes = date('H', $ts) * 60 + date('i', $ts);
        return $this->qb()
            ->selectRaw('metric_id, minute, sum(value) as value')
            ->where('id', '>=', $startId)
            ->where('minute', '<', $minutes)
            ->groupBy('metric_id')
            ->groupBy('minute')
            ->get();
    }

    public function getAggregatedMaxId(string $time = 'now'): int
    {
        $ts = strtotime($time);
        $minutes = date('H', $ts) * 60 + date('i', $ts);
        return $this->qb()
            ->selectRaw('max(id) max_id')
            ->where('minute', '<', $minutes)
            ->value('max_id');
    }
}