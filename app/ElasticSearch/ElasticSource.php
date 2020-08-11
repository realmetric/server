<?php
namespace App\ElasticSearch;

use Elasticsearch\Client;

class ElasticSource
{
    /**
     * @var Client
     */
    private $client;

    /**
     * ElasticSource constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $indexName
     * @param array $body
     * @return array|callable
     */
    public function create(string $indexName, array $body)
    {
        $params = [
            'index' => $indexName,
            'body' =>  $body
        ];

        return $this->client->index($params);
    }
    /**
     * @param $indexName
     */
    public function createIndexIfNotExist($indexName, array $body = [])
    {
        if (!$this->client->indices()->exists(['index' => $indexName])) {
            $this->debug(sprintf('Creating an index \'%s\'', $indexName));
            $params = [
                'index' => $indexName
            ];
            if ($body) {
                $params['body'] = $body;
            }

            $response = $this->client->indices()->create($params);
            $this->debug(sprintf('Index \'%s\' is created    %s', $indexName, json_encode($response)));
        }
    }

    public function agg(string $indexName, array $conditions, string $groupBy = null, string $aggFieldName = 'value')
    {
        if (!$this->client->indices()->exists($indexName)) {
            return [];
        }
        $query = [];

        foreach ($conditions as $key => $v) {
            $query['bool']['must'][] = ['match' => [$key => $v]];
        }

        if ($groupBy) {
            $aggs = [
                'by_' . $groupBy => [
                    'terms' => [
                        'field' => $groupBy
                    ],
                    'aggs' => [
                        'value' => [
                            'sum' => [
                                'field' => $aggFieldName
                            ]
                        ]
                    ],
                ]
            ];
        } else {
            $aggs = [
                'value' => [
                    'sum' => [
                        'field' => $aggFieldName
                    ]
                ]
            ];
        }
        $params = [
            'index' => $indexName,
            'size' => 0,
            'body' => [
                'aggs'  => $aggs,
                'query' => $query
            ]
        ];

        $response = $this->client->search($params);

        $result = $response['aggregations'];

        if ($groupBy) {
            return $this->prepareGroupResult($result['by_' . $groupBy]['buckets']);
        }

        return $result['value'];
    }

    private function prepareGroupResult(array $data)
    {
        $result = [];

        foreach ($data as $d) {
            $key = $d['key'];
            $value = $d['value']['value'];
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param string $mess
     */
    private function debug(string $mess)
    {
        echo $mess . PHP_EOL;
    }
}
