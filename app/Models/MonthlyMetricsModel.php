<?php

namespace App\Models;

class MonthlyMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'monthly_metrics';
    const TABLE = self::TABLE_PREFIX;

    public function __construct($connection)
    {
        parent::__construct($connection);

        $this->setTable(self::TABLE);
        $this->createTable($this->getTable());
    }

    protected function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedSmallInteger('metric_id');
            $table->double('value');
            $table->date('date');

            $table->index('metric_id');
            $table->unique(['metric_id', 'date']);
        });
    }

    public function getUnprocessedDailyMetricTableNames($dailyCounterTimestamp = null)
    {
        $q = $this->qb()
            ->select(['table_name'])
            ->from('information_schema.tables')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'LIKE', 'daily_metrics_%')
            ->orderBy('table_name');
        if ($dailyCounterTimestamp) {
            $tableName = 'daily_metrics_' . date('Y_m_d', $dailyCounterTimestamp);
            $q->where('table_name', '>', $tableName);
        }

        return $q->pluck('table_name');
    }

    public function getAggregatedDailyMetricsByTableName($dailyMetricsTableName)
    {
        $date = $this->getDateFromDailyMetricsTableName($dailyMetricsTableName);
        return $this->qb()
            ->selectRaw('metric_id, sum(value) value, \'' . $date . '\' date')
            ->from($dailyMetricsTableName)
            ->groupBy('metric_id')
            ->get();
    }

    public function updateOrInsert($row)
    {
        return $this->qb()->updateOrInsert(['metric_id' => $row['metric_id'], 'date' => $row['date']], $row);
    }

    public function getDateFromDailyMetricsTableName($dailyMetricsTableName)
    {
        return str_replace('_', '-', str_replace('daily_metrics_', '', $dailyMetricsTableName));
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

        return $q->get(['date', 'value']);
    }
}