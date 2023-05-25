<?php

namespace App\Model;

use Illuminate\Database\Connection;

class MonthlySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'monthly_slices';
    const TABLE = self::TABLE_PREFIX;

    public function __construct(Connection $connection)
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
            $table->unsignedInteger('metric_id');
            $table->unsignedInteger('slice_id');
            $table->double('value');
            $table->date('date');

            $table->index(['metric_id', 'slice_id']);
            $table->unique(['metric_id', 'slice_id', 'date']);
        });
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
        return $q->get(['date', 'value'])->all();
    }

    public function getTodayTotals(?int $metricId = null, ?int $sliceId = null)
    {
        $q = $this->qb()
            ->select(['slice_id as id', 'slices.name', 'slices.category', 'value as total'])
            ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id');
        if ($metricId) {
            $q->where('metric_id', $metricId);
        }
        if ($sliceId) {
            // @TODO group by slice
            $q->where('slice_id', $metricId);
        }
        return $q->where('date', date('Y-m-d'))
            ->get()->all();
    }

    public function getTotals(
        \DateTime $from,
        \DateTime $to,
                  $metricId = null,
                  $withNamesAndCategories = false
    ): array
    {
        $q = $this->qb();
        if ($withNamesAndCategories) {
            $q->selectRaw($this->getTable() . '.slice_id, slices.name, slices.category, SUM(' . $this->getTable() . '.value) as value')
                ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id')
                ->groupBy($this->getTable() . '.slice_id', 'slices.name', 'slices.category');
        } else {
            $q->selectRaw($this->getTable() . '.slice_id, SUM(' . $this->getTable() . '.value) as value')
                ->groupBy($this->getTable() . '.slice_id');
        }
        if ($metricId) {
            $q->where($this->getTable() . '.metric_id', '=', $metricId);
        }

        $q->where($this->getTable() . '.date', '>=', $from->format('Y-m-d'));
        $q->where($this->getTable() . '.date', '<=', $to->format('Y-m-d'));
//        $q->orderBy('value', 'desc');

        return $q->get()->all();
    }
}
