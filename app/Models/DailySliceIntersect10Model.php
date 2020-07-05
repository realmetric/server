<?php
declare(strict_types=1);

namespace App\Models;


class DailySliceIntersect10Model extends AbstractModel
{
    const SLICE_COLS_COUNT = 10;
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

        $this->shema()->create($name, function ($table) use ($name) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedInteger('metric_id');
            foreach (range(0, 9) as $sliceIndex) {
                $table->unsignedInteger('slice_' . $sliceIndex)->nullable($value = true);
            }
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('minute');


            $indexCols = ['metric_id'];
            foreach (range(0, 9) as $sliceIndex) {
                $indexCols[] = 'slice_' . $sliceIndex;
            }
            $table->index($indexCols, $name . '_metric_slices');
            $indexCols[] = 'minute';
            $table->index($indexCols, $name . '_metric_slices_minute');
        });
    }


    public function createBatchSlices(int $metricId, array $arrayOfSlices, int $value, int $minute)
    {
        $insertData = [];
        foreach ($arrayOfSlices as $slices) {
            $insertRow = [
                'metric_id' => $metricId,
                'value' => $value,
                'minute' => $minute
            ];

            if (count($slices) < self::SLICE_COLS_COUNT) {
                // fill empty slices
                $slices = array_merge($slices, array_fill(0, self::SLICE_COLS_COUNT - count($slices), null));
            }
            foreach ($slices as $index => $slice) {
                $insertRow['slice_' . $index] = $slice;
            }
            $insertData[] = $insertRow;
        }
        $this->insertBatch($insertData);
    }
}
