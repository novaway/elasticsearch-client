<?php

namespace Novaway\ElasticsearchClient;

use Novaway\ElasticsearchClient\Exception\InvalidConfigurationException;
use Novaway\ElasticsearchClient\Query\Result;
use Novaway\ElasticsearchClient\Query\ResultTransformer;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Serializers\SerializerInterface;
use Psr\Log\LoggerInterface;

class Index
{
    /** @var Client */
    protected $client;

    /** @var string */
    protected $name;

    /** @var array */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

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
        SerializerInterface $serializer = null,
        LoggerInterface $logger = null
    )
    {
        $this->name = $name;

        $this->logger = $logger;

        try {
            $clientBuilder = ClientBuilder::create()->setHosts($hosts);
            if ($serializer) {
                $clientBuilder->setSerializer($serializer);
            }
            $this->client = $clientBuilder->build();

            $this->loadConfig($indexConfig);

            if (!$this->client->indices()->exists(['index' => $this->getMainIndexName()])) {
                $this->create();
            }
        } catch (NoNodesAvailableException $ne) {
            $this->addLog('critical', sprintf("Error: Elasticsearch server is not available : %s", $ne->getMessage()), [
                'hosts' => $hosts,
                'name' => $name,
                'indexConfig' => $indexConfig,
                'exception' => $ne,
            ]);
        }
        catch (\Exception $e) {
            $this->addLog('critical', sprintf("Error: can not instantiate Elasticsearch server : %s", $ne->getMessage()), [
                'hosts' => $hosts,
                'name' => $name,
                'indexConfig' => $indexConfig,
                'exception' => $ne,
            ]);
        }
    }

    /**
     * Delete and recreate index from config
     */
    public function reload()
    {
        if ($this->client->indices()->exists(['index' => $this->getMainIndexName()])) {
            $this->client->indices()->delete(['index' => $this->getMainIndexName()]);
        }
        $this->create();
    }

    /**
     * @params array
     */
    public function index(array $params)
    {
        $params['index'] = $this->getMainIndexName();
        $this->client->index($params);
    }

    /**
     * @params array
     */
    public function delete(array $params)
    {
        $params['index'] = $this->getMainIndexName();

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
        // always search on the aliased index
        $searchParams['index'] = $this->getSearchIndexName();
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

    public function hotswapToTmp()
    {
        $this->createTmpIndex();
        // copy data from main to tmp
        $this->client->reindex([
            'body' => [
                'source' => [
                    'index' => $this->getMainIndexName()
                ],
                'dest' => [
                    'index' => $this->getTmpIndexName()
                ],
            ]
        ]);
        $this->setTmpAsAlias();
        // and the reload the main
        $this->reload();
    }

    public function hotswapToMain()
    {
        $this->setMainAsAlias();
        $this->client->indices()->delete(['index' => $this->getTmpIndexName()]);
    }

    /**
     * Create index from config
     */
    private function create()
    {
        $indexParams['index'] = $this->getMainIndexName();
        $indexParams['body'] = $this->config;

        $this->client->indices()->create($indexParams);

        $this->initAliasIfNoneExist();
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

    /**
     * Returns the alias of the current index
     *
     * @return string
     */
    public function getSearchIndexName(): string
    {
        return $this->name . '_alias';
    }

    public function getMainIndexName(): string
    {
        return $this->name;
    }

    private function getTmpIndexName(): string
    {
        return $this->name . '_tmp';
    }

    private function setMainAsAlias(): array
    {
        $this->removeParamsAsAlias($this->getTmpAliasParams());
        return $this->setParamsAsAlias($this->getMainAliasParams());
    }

    private function setTmpAsAlias(): array
    {
        $this->removeParamsAsAlias($this->getMainAliasParams());
        return $this->setParamsAsAlias($this->getTmpAliasParams());
    }

    private function getMainAliasParams(): array
    {
        return [
            'index' => $this->getMainIndexName(),
            'name' => $this->getSearchIndexName()
        ];
    }

    private function getTmpAliasParams(): array
    {
        return [
            'index' => $this->getTmpIndexName(),
            'name' => $this->getSearchIndexName()
        ];
    }

    private function removeParamsAsAlias(array $params)
    {
        if ($this->client->indices()->existsAlias($params) === true) {
            $this->client->indices()->deleteAlias($params);
        };
    }

    private function setParamsAsAlias(array $params): array
    {
        return $this->client->indices()->putAlias($params);
    }

    private function initAliasIfNoneExist()
    {
        if (!$this->client->indices()->existsAlias($this->getMainAliasParams())
            && !$this->client->indices()->existsAlias($this->getTmpAliasParams())
        ) {
            $this->setMainAsAlias();
        }
    }

    private function createTmpIndex()
    {
        if ($this->client->indices()->exists(['index' => $this->getTmpIndexName()])) {
            // delete index if already existing, to have a clean one
            $this->client->indices()->delete(['index' => $this->getTmpIndexName()]);
        }

        // create the tmp index
        $this->client->indices()->create([
            'index' => $this->getTmpIndexName()
        ]);
        // retrieve mappings from main index, to copy it to tmp
        $mapping = $this->client->indices()->getMapping(['index' => $this->getMainIndexName()]);
        foreach ($mapping[$this->getMainIndexName()]['mappings'] as $type => $mapping) {

            $this->client->indices()->putMapping([
                'index' => $this->getTmpIndexName(),
                'type' => $type,
                'body' => $mapping
            ]);
        }
    }

    protected function addLog(string $level, string $message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->$level($message, $context);
        }
    }
}
