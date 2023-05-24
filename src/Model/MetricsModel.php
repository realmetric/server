<?php


namespace App\Model;
use Illuminate\Database\Connection;


class MetricsModel extends AbstractModel
{
    const TABLE = 'metrics';

    private $cache = [];

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
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
            $table->unsignedInteger('name_crc_32');

            $table->unique('name_crc_32');
        });
    }

    private function fillCache()
    {
        if (count($this->cache)) {
            return;
        }

        $rows = $this->getAll();
        foreach ($rows as $row) {
            $this->cache[crc32($row['name'])] = $row;
        }
    }


    public function getOrCreate(string $name): array
    {
        $name = trim($name);
        $this->fillCache();

        $cacheKey = crc32($name);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $hash = crc32($name);

        // @TODO check collisions
        $exist = $this->qb()
            ->where('name_crc_32', $hash)
            ->first();
        if ($exist) {
//            $this->cache[$name] = $exist;
            return $exist;
        }

        $createdId = $this->create($name);
        return $this->getById($createdId);
    }

    public function create(string $name): int
    {
        $name = trim($name);
        $hash = crc32($name);

        return $this->insert([
            'name' => $name,
            'name_crc_32' => $hash,
        ]);
    }

    public function getId(string $name): int
    {
        $record = $this->getOrCreate($name);
        return $record['id'];
    }

}
