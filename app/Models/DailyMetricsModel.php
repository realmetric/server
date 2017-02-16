<?php

namespace App\Models;

class DailyMetricsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_metrics_';
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
          `metric_id` smallint UNSIGNED NOT NULL,
          `value` float NOT NULL,
          `minute` smallint UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `metric_id` (`metric_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;

        $this->qb()->query($sql);
    }

    public function create(int $metricId, float $value, string $time):int
    {
        $ts = strtotime($time);
        $minutes = date('H', $ts) * 60 + date('i', $ts);
        $result = $this->insert([
            'metric_id' => $metricId,
            'value' => $value,
            'minute' => $minutes,
        ]);
        var_dump($result);die;
    }
}