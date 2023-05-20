<?php

namespace App\Model;
use Illuminate\Database\Connection;

class CountersModel extends AbstractModel
{
    const TABLE_PREFIX = '';
    const TABLE = self::TABLE_PREFIX . 'counters';

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
            $table->string('name');
            $table->unsignedInteger('value');
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