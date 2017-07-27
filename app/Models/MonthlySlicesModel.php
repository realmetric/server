<?php

namespace App\Models;

class MonthlySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'monthly_slices';
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
            $table->unsignedSmallInteger('slice_id');
            $table->double('value');
            $table->date('date');

            $table->index(['metric_id', 'slice_id']);
            $table->unique(['metric_id', 'slice_id', 'date']);
        });
    }

    public function getUnprocessedDailySlicesTableNames($dailyCounterTimestamp = null)
    {
        $q = $this->qb()
            ->select(['table_name'])
            ->from('information_schema.tables')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'LIKE', 'daily_slices_%')
            ->orderBy('table_name');
        if ($dailyCounterTimestamp) {
            $tableName = 'daily_slices_' . date('Y_m_d', $dailyCounterTimestamp);
            $q->where('table_name', '>', $tableName);
        }

        return $q->pluck('table_name');
    }

    public function getAggregatedDailySlicesByTableName($dailyMetricsTableName)
    {
        $date = $this->getDateFromDailySlicesTableName($dailyMetricsTableName);
        return $this->qb()
            ->selectRaw('metric_id, slice_id, sum(value) value, \'' . $date . '\' date')
            ->from($dailyMetricsTableName)
            ->groupBy('metric_id', 'slice_id')
            ->get();
    }

    public function updateOrInsert($row)
    {
        return $this->qb()->updateOrInsert([
            'metric_id' => $row['metric_id'],
            'slice_id' => $row['slice_id'],
            'date' => $row['date']
        ], $row);
    }

    public function getDateFromDailySlicesTableName($dailySlicesTableName)
    {
        return str_replace('_', '-', str_replace('daily_slices_', '', $dailySlicesTableName));
    }

    public function getValues(int $metricId, int $sliceId, \DateTime $from = null, \DateTime $to = null): array
    {
        $q = $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId);
        if ($from) {
            $q->where('date', '>=', $from->format('Y-m-d'));
        }
        if ($to) {
            $q->where('date', '<=', $to->format('Y-m-d'));
        }
        return $q->get(['date', 'value']);
    }

    public function getTotals(
        \DateTime $from,
        \DateTime $to,
        $metricId = null,
        $withNamesAndCategories = false
    ): array {
        $q = $this->qb();
        if ($withNamesAndCategories) {
            $q->selectRaw($this->getTable() . '.slice_id, slices.name, slices.category, SUM(' . $this->getTable() . '.value) as value')
                ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id')
                ->groupBy($this->getTable() . '.slice_id', 'slices.name', 'slices.category');
        } else {
            $q->selectRaw($this->getTable() . '.slice_id, SUM(' . $this->getTable() . '.value) as value')
                ->groupBy($this->getTable() . '.slice_id');
        }
        if ($metricId){
            $q->where($this->getTable() . '.metric_id', '=', $metricId);
        }

        $q->where($this->getTable() . '.date', '>=', $from->format('Y-m-d'));
        $q->where($this->getTable() . '.date', '<=', $to->format('Y-m-d'));
//        $q->orderBy('value', 'desc');

        return $q->get();

    }
}