<?php
declare(strict_types=1);

namespace App\Model;


class DailyRawAllSlicesModel extends AbstractModel
{
    const SLICE_COLS_COUNT = 20;
    const TABLE_PREFIX = 'daily_raw_all_slices_';
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
            foreach (range(0, self::SLICE_COLS_COUNT) as $sliceIndex) {
                $table->unsignedInteger('slice_' . $sliceIndex)->nullable($value = true);
            }
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('minute');


            $table->index(['minute']);
        });
    }

    public function create(int $metricId, array $slices, int $value, int $minute)
    {
        sort($slices);

        $insertRow = [
            'metric_id' => $metricId,
            'value' => $value,
            'minute' => $minute
        ];

        foreach ($slices as $index => $slice) {
            $insertRow['slice_' . $index] = $slice;
        }

        $this->insert($insertRow);
    }


    public function getAllByMinute(int $minute) {
        return $this->qb()
            ->where('minute', '=', $minute)
            ->get();
    }
}
