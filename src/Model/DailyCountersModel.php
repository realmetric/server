<?php

namespace App\Model;

class DailyCountersModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_counters_';
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
            $table->string('name');
            $table->unsignedBigInteger('value');

            $table->index('name');

        });
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }

    public function getByName(string $name)
    {
        return $this->qb()
            ->where('name', '=', $name)
            ->first();
    }

    public function getValue(string $name):int
    {
        $value = $this->qb()
            ->where('name', '=', $name)
            ->value('value');
        return (int)$value;
    }

    public function updateOrInsert(string $name, int $value) : bool
    {
        return $this->qb()->updateOrInsert(['name' => $name], ['value' => $value]);
    }

}
