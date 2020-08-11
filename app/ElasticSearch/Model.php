<?php

namespace App\ElasticSearch;


class Model
{
    /**
     * @var ElasticSource
     */
    private $elasticSource;

    public function __constructor()
    {
        $clientBuilder = \Elasticsearch\ClientBuilder::create()->setHosts([
            '94.130.68.232:9200',
            '94.130.70.151:9200',
            '94.130.69.165:9200',
        ]);
        $client = $clientBuilder->build();
        $this->elasticSource = new ElasticSource($client);
    }

    /**
     * @param \DateTime $date
     * @param int $metricId
     * @param array $slices
     * @return array
     */
    public function minutes(\DateTime $date, int $metricId, array $slices): array
    {
        $indexName = 'realmetric_' . $date->format('Y-d-m');
        $conditions = ['metric_id' => $metricId];

        foreach ($slices as $sliceId) {
            $conditions["slice_{$sliceId}"] = 1;
        }

        return $this->elasticSource->agg($indexName, $conditions, 'minute', 'value');
    }
}
