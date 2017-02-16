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
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `{$name}` (
          `id` int  UNSIGNED AUTO_INCREMENT NOT NULL,
          `event_id` int UNSIGNED NOT NULL,
          `metric_id` int UNSIGNED NOT NULL,
          `slice_id` int UNSIGNED NOT NULL,
          `value_id` int UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `event_id` (`event_id`),
          KEY `metric_id` (`metric_id`),
          KEY `slice_id` (`slice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;

        $this->qb()->query($sql);
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