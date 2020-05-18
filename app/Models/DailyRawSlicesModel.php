<?php


namespace App\Models;


class DailyRawSlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_raw_slices_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($connection)
    {
        parent::__construct($connection);

        $this->setTableFromTimestamp(time());
    }

    public function setTableFromTimestamp(int $timestamp)
    {
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d_H', $timestamp));
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
            $table->unsignedInteger('minute');
        });
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }

    public function create(int $metricId, int $sliceId, float $value, string $time): int
    {
        $ts = strtotime($time);
        $minute = date('H', $ts) * 60 + date('i', $ts);
        return $this->insert([
            'metric_id' => $metricId,
            'slice_id' => $sliceId,
            'value' => $value,
            'minute' => $minute,
        ]);
    }

    public function getValues(int $metricId, int $sliceId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value']);
    }

    public function getMaxIdForTime(int $ts): int
    {
        $minute = date('H', $ts) * 60 + date('i', $ts);
        return $this->qb()
            ->selectRaw('max(id) as max_id')
            ->where('minute', '<', $minute)
            ->value('max_id') ?: 0;
    }

    public function getMaxId(): int
    {
        return $this->qb()
            ->selectRaw('max(id) as max_id')
            ->value('max_id') ?: 0;
    }

    public function getAggregatedData(int $minId, int $lastId): array
    {
//        $result = [];
//        $allSliceIds = $this->qb()
//            ->distinct()
//            ->where('id', '>=', $minId)
//            ->where('id', '<=', $lastId)
//            ->pluck('slice_id');
//
//        foreach ($allSliceIds as $sliceId){
//            $sliceIds[] = $sliceId;
//            if (count($sliceIds) > 200){
//                $res = $this->qb()
//                    ->selectRaw('metric_id, minute, slice_id, sum(value) as value')
//                    ->where('id', '>=', $minId)
//                    ->where('id', '<=', $lastId)
//                    ->whereIn('slice_id', $sliceIds)
//                    ->groupBy(['metric_id', 'minute', 'slice_id'])
//                    ->get();
//                $result = array_merge($result, $res);
//                $sliceIds = [];
//            }
//        }
//        if (!empty($sliceIds)){
//            $res = $this->qb()
//                ->selectRaw('metric_id, minute, slice_id, sum(value) as value')
//                ->where('id', '>=', $minId)
//                ->where('id', '<=', $lastId)
//                ->whereIn('slice_id', $sliceIds)
//                ->groupBy(['metric_id', 'minute', 'slice_id'])
//                ->get();
//            $result = array_merge($result, $res);
//        }
//
//        return $result;
//        $this->getConnection()->select();
        return $this->qb()
            ->selectRaw($this->getTable() . '.metric_id, ' . $this->getTable() . '.minute, ' . $this->getTable() .  '.slice_id, sum(value) as value')
//            ->leftJoin('daily_slices_2017_07_25', function($join){
//                $join->on('daily_slices_2017_07_25.metric_id', '=', $this->getTable() .'.metric_id');
//                $join->on('daily_slices_2017_07_25.slice_id', '=', $this->getTable() .'.slice_id');
//                $join->on('daily_slices_2017_07_25.minute', '=', $this->getTable() .'.minute');
//            })
            ->where($this->getTable() . '.id', '>=', $minId)
            ->where($this->getTable() . '.id', '<=', $lastId)
            ->groupBy([$this->getTable() .'.metric_id', $this->getTable() . '.minute', $this->getTable() . '.slice_id'])
            ->get();
    }
}
