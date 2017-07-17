<?php

namespace App\Models;

class DailyMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_metrics_';
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
            $table->integer('value');
            $table->unsignedSmallInteger('minute');

            $table->index('metric_id');
            $table->unique(['metric_id', 'minute']);
        });
    }

    public function createOrIncrement(int $metricId, int $value, int $minute) : int
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

    public function getTotals(int $timestamp, bool $withNames = false)
    {
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        $q = $this->qb();
        if ($withNames) {
            $q->selectRaw($this->getTable() . '.metric_id, metrics.name, sum(' . $this->getTable() . '.value) as value')
                ->join('metrics', $this->getTable() . '.metric_id', '=', 'metrics.id')
                ->groupBy($this->getTable() . '.metric_id', 'metrics.name');

        } else {
            $q->selectRaw($this->getTable() . '.metric_id, sum('. $this->getTable() .'.value) as value')
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