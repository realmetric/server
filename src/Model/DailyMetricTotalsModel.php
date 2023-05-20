<?php


namespace App\Model;
use Illuminate\Database\Connection;


class DailyMetricTotalsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_metric_totals_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct(Connection $connection)
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

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedBigInteger('metric_id');
            $table->bigInteger('value');
            $table->float('diff')->default(0);

            $table->unique(['metric_id']);
        });
    }

    public function getTotals(bool $withNames = false)
    {
        $q = $this->qb();
        if ($withNames) {
            $q->selectRaw($this->getTable() . '.metric_id as id, metrics.name, sum(' . $this->getTable() . '.value) as total, ' . $this->getTable() . '.diff')
                ->join('metrics', $this->getTable() . '.metric_id', '=', 'metrics.id')
                ->groupBy($this->getTable() . '.metric_id', 'metrics.name', $this->getTable() . '.diff');

        } else {
            $q->selectRaw($this->getTable() . '.metric_id, sum('. $this->getTable() .'.value) as value, ' . $this->getTable() . '.diff')
                ->groupBy($this->getTable() . '.metric_id', $this->getTable() . '.diff');
        }

        return $q->get();
    }

}
