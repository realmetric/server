<?php


namespace App\Model;
use Illuminate\Database\Connection;

class SlicesModel extends AbstractModel
{
    const TABLE = 'slices';

    private $cache = [];

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        if ($this->shema()->hasTable($this->getTable())) {
            return;
        }
        $this->shema()->create($this->getTable(), function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('category');
            $table->unsignedInteger('category_crc_32');
            $table->string('name');
            $table->unsignedInteger('name_crc_32');

            $table->unique(['category_crc_32', 'name_crc_32']);
        });
    }


    private function fillCache()
    {
        if (count($this->cache)) {
            return;
        }

        $rows = $this->getAll();
        foreach ($rows as $row) {
            $this->cache[crc32($row['category'] . ':' . $row['name'])] = $row;
        }
    }

    public function getOrCreate(string $category, string $name): array
    {
        $category = trim($category);
        $name = trim($name);
        $this->fillCache();

        $cacheKey = crc32($category . ':' . $name);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $categoryCrc32 = crc32($category);
        $nameCrc32 = crc32($name);

        // @TODO check collisions
        $exist = $this->qb()
            ->where('category_crc_32', $categoryCrc32)
            ->where('name_crc_32', $nameCrc32)
            ->first();
        if ($exist) {
//            $this->cache[$cacheKey] = $exist;
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
