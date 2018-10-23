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
    protected $client;
    /** @var string */
    protected $name;
    /** @var string */
    protected $tmpName;
    /** @var array */
    protected $config;

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
        $this->tmpName = $name . "_tmp";

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
        $this->clearIndex($this->name);
        $this->create($this->name);
    }

    public function reloadTmp()
    {
        $this->clearIndex($this->tmpName);
        $this->create($this->tmpName);
    }

    /**
     * @params array
     */
    public function index(array $params)
    {
        $this->reloadIndex($this->name, $params);
    }

    /**
     * @params array
     */
    public function indexTmp(array $params)
    {
        $this->reloadIndex($this->tmpName, $params);
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

    public function reindexTmpToMain()
    {
        // reload main index to ensure settings are correct
        $this->reload();
        // reindex documents from tmp to main index
        $this->client->reindex([
            'body' =>
                [
                    'source' => [
                        'index' => $this->tmpName
                    ],
                    'dest' => [
                        'index' => $this->name
                    ],
                ],
            'wait_for_completion' => true
        ]);
        // and remove the now obsolete tmp index
        $this->clearIndex($this->tmpName);
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
        $limit = $searchParams['body']['size'] ??  null;

        $result = Result::createFromArray($searchResult, $limit);

        if ($resultTransformer) {
            $result = $resultTransformer->formatResult($result);
            if ($result->getLimit() === null) {
                // keep limit if it has not been set by the transformer
                $result->setLimit($limit);
            }
        }
        return $result;
    }

    /**
     * Create index from config
     */
    private function create(string $name)
    {
        $indexParams['index'] = $name;
        $indexParams['body'] = $this->config;

        $this->client->indices()->create($indexParams);
    }

    private function createTmp()
    {
        $indexParams['index'] = $this->tmpName;
        $indexParams['body'] = $this->config;

        $this->client->indices()->create($indexParams);
    }

    private function reloadIndex(string $name, array $params)
    {
        $params['index'] = $name;
        $this->client->index($params);
    }

    private function clearIndex(string $name)
    {
        if ($this->client->indices()->exists(['index' => $name])) {
            $this->client->indices()->delete(['index' => $name]);
        }
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

        $this->config['settings'] = $indexConfig['settings'] ?? [];

        foreach ($indexConfig['mappings'] as $typeName => $typeMapping) {
            if (isset($typeMapping)) {
                $this->config['mappings'][$typeName] = $typeMapping;
            }
        }
    }

}
