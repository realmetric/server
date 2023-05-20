<?php

namespace App\Model;

class DailyRawMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_raw_metrics_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01_13'; // Just for example

    public function __construct($connection)
    {
        parent::__construct($connection);

        $this->setTableFromTimestamp(time());
    }

    public function setTableFromTimestamp(int $timestamp)
    {
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d_H', $timestamp));
        $this->createTableIfNotExists();
        return $this;
    }

    protected function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedInteger('metric_id');
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('minute');
        });
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
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

    public function getMaxIdForTime(int $timestamp): int
    {
        $minute = date('H', $timestamp) * 60 + date('i', $timestamp);
        return $this->qb()
            ->selectRaw('max(id) as max_id')
            ->where('minute', '<', $minute)
            ->value('max_id') ?: 0;
    }

    public function getAggregatedDataByRange(int $firstId, int $lastId): array
    {
        return $this->qb()
            ->selectRaw('metric_id, minute, sum(value) as value')
            ->where('id', '>=', $firstId)
            ->where('id', '<=', $lastId)
            ->groupBy(['metric_id', 'minute'])
            ->get();
    }

}
