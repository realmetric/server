<?php


namespace App\Model;

use Illuminate\Database\Connection;


class DailySliceTotalsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slice_totals_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->setTableFromTimestamp(time());
    }

    public function setTableFromTimestamp(int $timestamp)
    {
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d', $timestamp));
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
            $table->unsignedInteger('slice_id');
            $table->unsignedBigInteger('value');
            $table->float('diff')->default(0);

            $table->unique(['metric_id', 'slice_id']);
        });
    }

    public function getMetricsWithSlice(int $sliceId)
    {
        return $this->qb()->where('slice_id', $sliceId)->distinct()->get(['metric_id'])->pluck('metric_id')->all();
    }

    public function getAllValues()
    {
        return $this->qb()->selectRaw('slice_id as id, value, diff')
            ->get()->all();
    }

    public function getTotals($metricId = null, bool $withNamesAndCategories = false): array
    {
        $q = $this->qb();
        if ($withNamesAndCategories) {
            $q->selectRaw($this->getTable() . '.slice_id as id, slices.name, slices.category, ' . $this->getTable() . '.value as total, ' . $this->getTable() . '.diff')
                ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id');
        } else {
            $q->selectRaw($this->getTable() . '.slice_id as id, ' . $this->getTable() . '.value as total, ' . $this->getTable() . '.diff');
        }
        if ($metricId) {
            $q->where($this->getTable() . '.metric_id', '=', $metricId);
        }

        return $q->get()->all();
    }
}
