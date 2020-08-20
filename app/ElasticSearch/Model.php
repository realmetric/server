<?php

namespace App\ElasticSearch;


class Model
{
    /**
     * @var ElasticSource
     */
    private $elasticSource;

    public function __construct()
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
        $mId = (string)$metricId;
        $indexName = sprintf('realmetric_%s_%s', $date->format('Y-m-d'), $mId[0]);
        $result = $this->elasticSource->agg($indexName, $metricId, $slices, 'minute', 'value');
        ksort($result);
        return $result;
    }
}
