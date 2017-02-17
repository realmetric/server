<?php


namespace App\Models;


class MetricsModel extends AbstractModel
{
    const TABLE = 'metrics';

    public function __construct($queryBuilder)
    {
        parent::__construct($queryBuilder);
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
            $table->string('name');
            $table->unsignedInteger('name_crc_32');

            $table->unique('name');
            $table->index('name_crc_32');
        });
    }

    public function getOrCreate(string $name):array
    {
        $name = trim($name);
        $hash = crc32($name);

        $exist = $this->qb()
            ->where('name_crc_32', $hash)
            ->where('name', $name)
            ->first();
        if ($exist) {
            return $exist;
        }

        $createdId = $this->create($name);
        return $this->getById($createdId);
    }

    public function create(string $name)
    {
        $name = trim($name);
        $hash = crc32($name);

        return $this->insert([
            'name' => $name,
            'name_crc_32' => $hash,
        ]);
    }

    public function getId(string $name):int
    {
        $record = $this->getOrCreate($name);
        return $record['id'];
    }
}