<?php
declare(strict_types=1);

namespace App\Models;


class DailySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slices_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($connection)
    {
        parent::__construct($connection);

        $this->setTable(self::TABLE_PREFIX . date('Y_m_d'));
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
            $table->integer('value');
            $table->unsignedSmallInteger('minute');

            $table->index(['metric_id', 'slice_id']);
            $table->index(['minute']);
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

    public function createOrIncrement(int $metricId, int $sliceId, int $value, int $minute): int
    {
        // Check exist
        $id = $this->qb()->where('metric_id', $metricId)
            ->where('slice_id', $sliceId)
            ->where('minute', $minute)
            ->value('id');

        // Increment instead Insert
        if ($id) {
            $this->increment($id, 'value', $value);
            return $id;
        }

        $data = [
            'metric_id' => $metricId,
            'slice_id' => $sliceId,
            'value' => $value,
            'minute' => $minute,
        ];
        return $this->insert($data);
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

        return $q->get();

    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }

    public function getDiff($metricId, $sliceId, $value, $minute) : float
    {
        $yesterdayValue = $this->qb()
            ->selectRaw('sum(value) as value')
            ->where('metric_id', $metricId)
            ->where('slice_id', $sliceId)
            ->where('minute', '<', $minute)
            ->value('value');

        if (!$yesterdayValue) {
            return (float) 0;
        }

        $diffPercent = (($value * 100) / $yesterdayValue) - 100;

        return (float)$diffPercent;
    }

    public function aggregate(int $timestamp)
    {
        //TODO: check table exists
        $dailySliceTotalsTable = DailySliceTotalsModel::TABLE_PREFIX . date('Y_m_d', $timestamp);
        $dailySlicesTable = DailySlicesModel::TABLE_PREFIX . date('Y_m_d', $timestamp);
        $dailySlicesYesterdayTable = DailySlicesModel::TABLE_PREFIX . date('Y_m_d', strtotime('-1 day', $timestamp));
        $minute = date('H', $timestamp) * 60 + date('i', $timestamp);

        $sql = <<<SQL
INSERT INTO $dailySliceTotalsTable (metric_id, slice_id, value, diff)
  SELECT s.metric_id, s.slice_id, s.val, (case s.sm
                                          -- when s.sm IS NULL then 0
                                          when 0 then 0
                                          else ((s.val * 100) / s.sm) - 100 END) AS diff
  FROM (SELECT
          daily_slices.metric_id,
          daily_slices.slice_id,
          sum(daily_slices.value) val,
          case when df.sm is null then 0
    else df.sm end as sm
        FROM $dailySlicesTable daily_slices
          LEFT JOIN (SELECT
                       daily_slices_diff.metric_id,
                       daily_slices_diff.slice_id,
                       sum(value) AS sm
                     FROM $dailySlicesYesterdayTable daily_slices_diff
                     WHERE daily_slices_diff.minute < $minute
                     GROUP BY daily_slices_diff.metric_id, daily_slices_diff.slice_id
                    ) df ON daily_slices.metric_id = df.metric_id AND
                            daily_slices.slice_id = df.slice_id
        GROUP BY daily_slices.metric_id,
          daily_slices.slice_id) s
ON DUPLICATE KEY UPDATE
  $dailySliceTotalsTable.value = s.val,
  $dailySliceTotalsTable.diff  = diff
SQL;

        return $this->getConnection()->getPdo()->query($sql, \PDO::FETCH_ASSOC);
    }
}