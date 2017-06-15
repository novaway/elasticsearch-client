<?php

namespace Novaway\ElasticsearchClient;

use Novaway\ElasticsearchClient\Exception\InvalidConfigurationException;
use Novaway\ElasticsearchClient\Query\Result;
use Novaway\ElasticsearchClient\Query\ResultTransformer;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Serializers\SerializerInterface;

class Index
{
    /** @var Client */
    private $client;

    /** @var string */
    private $name;

    /** @var array */
    private $config;

    /**
     * @param array $hosts
     * @param string $name
     * @param array $indexConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        array $hosts = [],
        $name,
        array $indexConfig = [],
        SerializerInterface $serializer = null
    )
    {
        $this->name = $name;

        $clientBuilder = ClientBuilder::create()->setHosts($hosts);
        if ($serializer) {
            $clientBuilder->setSerializer($serializer);
        }
        $this->client = $clientBuilder->build();

        $this->loadConfig($indexConfig);

        if (!$this->client->indices()->exists(['index' => $this->name])) {
            $this->create();
        }
    }

    /**
     * Delete and recreate index from config
     */
    public function reload()
    {
        if ($this->client->indices()->exists(['index' => $this->name])) {
            $this->client->indices()->delete(['index' => $this->name]);
        }
        $this->create();
    }

    /**
     * @params array
     */
    public function index(array $params)
    {
        $params['index'] = $this->name;
        $this->client->index($params);
    }

    /**
     * @params array
     */
    public function delete(array $params)
    {
        $params['index'] = $this->name;

        if ($this->client->exists($params) === true) {
            $this->client->delete($params);
        }
    }

    /**
     * @param array $searchParams
     *
     * @return Result
     */
    public function search(array $searchParams, ResultTransformer $resultTransformer = null)
    {
        $searchParams['index'] = $this->name;
        $searchResult = $this->client->search($searchParams);

        $result = Result::createFromArray($searchResult);

        if ($resultTransformer) {
            $result = $resultTransformer->formatResult($result);
        }

        return $result;
    }

    /**
     * Create index from config
     */
    private function create()
    {
        $indexParams['index'] = $this->name;
        $indexParams['body'] = $this->config;

        $this->client->indices()->create($indexParams);
    }

    /**
     * Reformat and store configuration
     *
     * @param array $indexConfig
     *d
     * @throws InvalidConfigurationException
     */
    private function loadConfig(array $indexConfig)
    {
        if (!isset($indexConfig['mappings'])) {
            throw new InvalidConfigurationException('Missing key "mappings" in search configuration.');
        }

        $this->config['settings'] = isset($indexConfig['settings']) ? $indexConfig['settings'] : [];

        foreach ($indexConfig['mappings'] as $typeName => $typeMapping) {
            if (isset($typeMapping)) {
                $this->config['mappings'][$typeName] = $typeMapping;
            }
        }
    }

}
