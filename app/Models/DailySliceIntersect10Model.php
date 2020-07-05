<?php
declare(strict_types=1);

namespace App\Models;


class DailySliceIntersect10Model extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slice_intersect10_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($connection)
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
            foreach (range(0, 9) as $sliceIndex) {
                $table->unsignedInteger('slice_' . $sliceIndex);
            }
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('minute');


            $indexCols = ['metric_id'];
            foreach (range(0, 9) as $sliceIndex) {
                $indexCols[] = 'slice_' . $sliceIndex;
            }
            $table->index($indexCols);
            $indexCols[] = 'minute';
            $table->index($indexCols);
        });
    }


    public function createOrIncrement(int $metricId, array $slices, int $value, int $minute): int
    {
        sort($slices);

        // Check exist
        $q = $this->qb()->where('metric_id', $metricId);
        foreach ($slices as $sliceIndex => $slice) {
            $q->where('slice_' . $sliceIndex, $slice);
        }
        $q->where('minute', $minute);
        $id = $q->value('id');

        // Increment instead Insert
        if ($id) {
            $this->increment($id, 'value', $value);
            return $id;
        }

        // Creating new record
        $insertData = [
            'metric_id' => $metricId,
            'minute' => $minute,
            'value' => $value,
        ];
        foreach ($slices as $sliceIndex => $slice) {
            $insertData['slice_' . $sliceIndex] = $slice;
        }
        return $this->insert($insertData);
    }


}
