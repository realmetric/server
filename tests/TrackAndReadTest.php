<?php

namespace App\Tests;

use App\Library\EventSaver;
use App\Model\MetricsModel;
use App\Model\MonthlyMetricsModel;
use App\Model\MonthlySlicesModel;
use App\Model\SlicesModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TrackAndReadTest extends KernelTestCase
{
    public function test()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        /** @var EventSaver $eventSaver */
        $eventSaver = $container->get(EventSaver::class);
        $metricName = 'test';
        $sliceCategory = 'quality';
        $sliceName = 'best';
        $value = 3;
        $time = strtotime('10 days ago');
        $date = date('Y-m-d', $time);
        $eventSaver->save('test', $value, $time, [$sliceCategory => $sliceName]);

        /** @var MetricsModel $metricsModel */
        $metricsModel = $container->get(MetricsModel::class);
        $metricId = $metricsModel->getId($metricName);

        /** @var SlicesModel $slicesModel */
        $slicesModel = $container->get(SlicesModel::class);
        $sliceId = $slicesModel->getId($sliceCategory, $sliceName);

        /** @var MonthlyMetricsModel $monthlyMetricsModel */
        $monthlyMetricsModel = $container->get(MonthlyMetricsModel::class);
        $this->assertEquals(
            [['date' => $date, 'value' => $value]],
            $monthlyMetricsModel->getByMetricId($metricId)
        );

        /** @var MonthlySlicesModel $monthlySlicesModel */
        $monthlySlicesModel = $container->get(MonthlySlicesModel::class);
        $this->assertEquals([
            ['date' => $date, 'value' => $value]],
            $monthlySlicesModel->getValues($metricId, $sliceId)
        );
    }
}
