<?php


namespace App\Models;


class DailySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slices_';
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
            $table->unsignedSmallInteger('slice_id');
            $table->float('value');
            $table->unsignedSmallInteger('minute');

            $table->index(['metric_id', 'slice_id']);
            $table->unique(['metric_id', 'slice_id', 'minute']);
        });
    }

    public function getValues(int $metricId, int $sliceId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value']);
    }

    public function getAllByMetricId(int $metricId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->get(['slice_id', 'minute', 'value']);
    }

    public function getMetricsBySlice(int $sliceId)
    {
        return $this->qb()->where('slice_id', $sliceId)
            ->groupBy('metric_id')
            ->pluck('metric_id');
    }


    public function getTotalsByMetricId(int $metricId, int $timestamp, $withNamesAndCategories = false): array
    {
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        $q = $this->qb();
        if ($withNamesAndCategories) {
            $q->selectRaw($this->getTable() . '.slice_id, slices.name, slices.category, sum(' . $this->getTable() . '.value) as value')
                ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id')
                ->groupBy($this->getTable() . '.slice_id', 'slices.name', 'slices.category');

        } else {
            $q->selectRaw($this->getTable() . '.slice_id, sum(' . $this->getTable() . '.value) as value')
                ->groupBy($this->getTable() . '.slice_id');
        }
        $q->where($this->getTable() . '.metric_id', '=', $metricId)
            ->where($this->getTable() . '.minute', '<', $minute);

        return $q->get();

    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }
}