<?php


namespace App\Models;


class SlicesModel extends AbstractModel
{
    const TABLE = 'slices';

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
            $table->string('category');
            $table->unsignedInteger('category_crc_32');
            $table->string('name');
            $table->unsignedInteger('name_crc_32');
            $table->index(['category_crc_32', 'name_crc_32']);
        });
    }

    public function getOrCreate(string $category, string $name): array
    {
        $category = trim($category);
        $categoryCrc32 = crc32($category);
        $name = trim($name);
        $nameCrc32 = crc32($name);

        $exist = $this->qb()
            ->where('category_crc_32', $categoryCrc32)
            ->where('name_crc_32', $nameCrc32)
            ->where('category', $category)
            ->where('name', $name)
            ->first();
        if ($exist) {
            return $exist;
        }

        $createdId = $this->create($category, $name);
        return $this->getById($createdId);
    }

    public function getId(string $category, string $name): int
    {
        $record = $this->getOrCreate($category, $name);
        return $record['id'];
    }

    public function create(string $category, string $name): int
    {
        $category = trim($category);
        $categoryCrc32 = crc32($category);
        $name = trim($name);
        $nameCrc32 = crc32($name);

        return $this->insert([
            'category' => $category,
            'category_crc_32' => $categoryCrc32,
            'name' => $name,
            'name_crc_32' => $nameCrc32,
        ]);
    }

}