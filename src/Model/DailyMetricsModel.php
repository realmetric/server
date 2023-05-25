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

        $this->setTable(self::TABLE_PREFIX . date('Y_m_d', time()));
        if ($this->shema()->hasTable($this->getTable())) {
            return;
        }
        $this->shema()->create($this->getTable(), function ($table) {
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

    public function getYesterdayValues(int $metricId): array
    {
        return $this->qb()->from(self::TABLE_PREFIX . date('Y_m_d', strtotime('yesterday')))
            ->where('metric_id', '=', $metricId)
            ->get(['minute', 'value'])->all();
    }

    public function getTodayValues(int $metricId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->get(['minute', 'value'])->all();
    }
}
