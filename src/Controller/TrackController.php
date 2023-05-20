<?php

namespace App\Controller;

use App\Library\EventSaver;
use App\Model\DailyMetricsModel;
use App\Model\DailyMetricTotalsModel;
use App\Model\DailySliceTotalsModel;
use App\Model\MetricsModel;
use App\Model\SlicesModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TrackController extends AbstractController
{
    public function __construct(
        private readonly EventSaver             $eventSaver,
        private readonly MetricsModel           $metrics,
        private readonly SlicesModel            $slices,
        private readonly DailyMetricsModel      $dailyMetrics,
        private readonly DailyMetricTotalsModel $dailyMetricTotals,
        private readonly DailySliceTotalsModel  $dailySliceTotals,
    )
    {
    }

    #[Route('/track', methods: ['POST'])]
    public function create()
    {
        $data = gzuncompress(file_get_contents('php://input'));
        $events = json_decode($data, true);

        foreach ($events as $event) {
            $this->eventSaver->save($event['m'], $event['v'], $event['t'], $event['s']);
        }
        return $this->json(['createdEvents' => count($events)]);
    }

    #[Route('/track/testdata', methods: ['GET'])]
    public function createTest()
    {
        $metricId = $this->metrics->getId('testMetric');
        $sliceId = $this->slices->getId('testSlice','testValue');
        $value = 1;
        $this->dailyMetrics
            ->setTableFromTimestamp(time())
            ->createOrIncrement($metricId, $value, date('i'));
        $this->dailyMetricTotals->insertOrUpdate([
            'metric_id' => $metricId,
            'value' => 1,
        ]);
        $this->dailySliceTotals->create($metricId, $sliceId, 1);

        return $this->json(['createdEvents' => 1]);
    }
}
