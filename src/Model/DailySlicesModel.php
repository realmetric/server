<?php
declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Connection;


class DailySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slices_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d', time()));
        if (!$this->schema()->hasTable($this->getTable())) {
            $this->createDSTable($this->getTable());
        }
        $yesterdayTable = self::TABLE_PREFIX . date('Y_m_d', strtotime('yesterday'));
        if (!$this->schema()->hasTable($yesterdayTable)) {
            $this->createDSTable($yesterdayTable);
        }
    }

    private function createDSTable($name)
    {
        $this->schema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedInteger('metric_id');
            $table->unsignedInteger('slice_id');
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('minute');

            $table->index(['metric_id', 'slice_id']);
            $table->index(['minute']);
            $table->unique(['metric_id', 'slice_id', 'minute']);
        });
    }


    public function getYesterdayValues(int $metricId, int $sliceId): array
    {
        return $this->qb()->from(self::TABLE_PREFIX . date('Y_m_d', strtotime('yesterday')))
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value'])->all();
    }

    public function getTodayValues(int $metricId, int $sliceId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value'])->all();
    }

    public function getTotals(int $timestamp, $metricId = null, bool $withNamesAndCategories = false): array
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
        if ($metricId) {
            $q->where($this->getTable() . '.metric_id', '=', $metricId);
        }

        $q->where($this->getTable() . '.minute', '<', $minute);

        return $q->get()->all();
    }
}
