<?php

namespace App\Model;
use Illuminate\Database\Connection;


class DailyMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_metrics_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->setTableFromTimestamp(time());
    }

    public function setTableFromTimestamp(int $timestamp, bool $createTableIfNotExists = true)
    {
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d', $timestamp));
        if ($createTableIfNotExists) {
            $this->createTableIfNotExists();
        }
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

            $table->index('metric_id');
            $table->index('minute');
            $table->unique(['metric_id', 'minute']);
        });
    }

    public function createOrIncrement(int $metricId, int $value, int $minute): int
    {
        // Check exist
        $id = $this->qb()->where('metric_id', $metricId)
            ->where('minute', $minute)
            ->value('id');

        // Increment instead Insert
        if ($id) {
            $this->increment($id, 'value', $value);
            return $id;
        }

        $data = [
            'metric_id' => $metricId,
            'value' => $value,
            'minute' => $minute,
        ];
        return $this->insert($data);
    }

    public function getMetricsByMinute(int $minute)
    {
        return $this->qb()
            ->where('minute', $minute)
            ->distinct()
            ->pluck('metric_id');
    }

    public function getTotals(int $timestamp, bool $withNames = false)
    {
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        $q = $this->qb();
        if ($withNames) {
            $q->selectRaw($this->getTable() . '.metric_id, metrics.name, sum(' . $this->getTable() . '.value) as value')
                ->join('metrics', $this->getTable() . '.metric_id', '=', 'metrics.id')
                ->groupBy($this->getTable() . '.metric_id', 'metrics.name');

        } else {
            $q->selectRaw($this->getTable() . '.metric_id, sum(' . $this->getTable() . '.value) as value')
                ->groupBy('metric_id');
        }

        $q->where($this->getTable() . '.minute', '<', $minute);
        return $q->get();
    }

    public function getByMetricId(int $metricId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->get(['minute', 'value']);
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }

}
