<?php

namespace App\Model;

use Illuminate\Database\Connection;

class MonthlyMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'monthly_metrics';
    const TABLE = self::TABLE_PREFIX;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->setTable(self::TABLE);
        if ($this->schema()->hasTable($this->getTable())) {
            return;
        }

        $this->schema()->create($this->getTable(), function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedInteger('metric_id');
            $table->double('value');
            $table->date('date');

            $table->index('metric_id');
            $table->unique(['metric_id', 'date']);
        });
    }

    public function getTodayTotals()
    {
        return $this->qb()
            ->select(['metric_id as id', 'metrics.name', 'value as total'])
            ->join('metrics', $this->getTable() . '.metric_id', '=', 'metrics.id')
            ->where('date', date('Y-m-d'))
            ->get()->all();
    }

    public function getByMetricId(int $metricId, \DateTime $from = null, \DateTime $to = null): array
    {
        $q = $this->qb()
            ->where('metric_id', '=', $metricId);
        if ($from) {
            $q->where('date', '>=', $from->format('Y-m-d'));
        }
        if ($to) {
            $q->where('date', '<=', $to->format('Y-m-d'));
        }
        return $q->get(['date', 'value'])->all();
    }
}
