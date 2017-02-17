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
            $table->unsignedInteger('event_id');
            $table->unsignedSmallInteger('metric_id');
            $table->unsignedSmallInteger('slice_id');
            $table->unsignedSmallInteger('value_id');
            $table->index('event_id');
            $table->index('metric_id');
        });
    }

    public function create(int $eventId, int $metricId, int $sliceId, int $valueId):int
    {
        return $this->insert([
            'event_id' => $eventId,
            'metric_id' => $metricId,
            'slice_id' => $sliceId,
            'value_id' => $valueId,
        ]);
    }
}