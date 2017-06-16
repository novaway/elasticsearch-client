<?php

namespace Test\Functional\Novaway\ElasticsearchClient\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Ring\Client\CurlHandler;
use mageekguy\atoum\asserter\generator as AssertGenerator;
use Novaway\ElasticsearchClient\Index;
use Novaway\ElasticsearchClient\ObjectIndexer;
use Symfony\Component\Yaml\Yaml;
use Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos\IndexableObject;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var AssertGenerator */
    private $assert;
    /** @var array */
    private $defaultConfiguration;

    /**
     * FeatureContext constructor.
     */
    public function __construct()
    {
        $this->assert = new AssertGenerator();
        $this->defaultConfiguration = Yaml::parse(file_get_contents(__DIR__.'/data/config.yml'));
    }

    /**
     * @Given there is no index named :indexName
     */
    public function thereIsNoIndexNamed(string $indexName)
    {
        $status = $this->httpGetStatus(\sprintf('/%s/', $indexName));

        if ($status === 404) {
            return;
        }

        $this->httpDelete('/my_index/');
    }

    /**
     * @Given there is an index named :indexName
     */
    public function thereIsAnIndexNamed(string $indexName)
    {
        $status = $this->httpGetStatus(\sprintf('/%s/', $indexName));
        $this->assert->integer($status)->isEqualTo(200);
    }

    /**
     * @When I create an index named :indexName with the configuration from :configFile
     */
    public function iCreateAnIndexNamedWithTheConfigurationFrom(string $indexName, string $configFile)
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/'.$configFile));
        $this->getIndex($indexName, $config);
    }

    /**
     * @Then the index named :indexName should exist
     */
    public function theIndexNamedShouldExist(string $indexName)
    {
        $status = $this->httpGetStatus(\sprintf('/%s/', $indexName));

        $this->assert->integer($status)->isEqualTo(200);
    }

    /**
     * @Then The index named :indexName mapping be mapped with :
     * @Then the mapping of the index named :indexName for the type :typeName should be :
     */
    public function theIndexNamedMappingBeMappedWith(string $indexName, string $typeName, TableNode $mappingData)
    {
        $response = $this->httpGet(\sprintf('/%s/', $indexName));

        $mappingDataHash = $mappingData->getHash();
        foreach ($mappingDataHash as $mappingDataRow) {
            $this->assert->string($response[$indexName]['mappings'][$typeName]['properties'][$mappingDataRow['property']]['type'])->isEqualTo($mappingDataRow['type']);
            if($mappingDataRow['analyzer']) {
                $this->assert->string($response[$indexName]['mappings'][$typeName]['properties'][$mappingDataRow['property']]['analyzer'])->isEqualTo($mappingDataRow['analyzer']);
            }
        }
    }

    /**
     * @Given the index named :indexName is not new
     */
    public function theIndexNamedIsNotNew(string $indexName)
    {
        if ($this->countIndexInsertion($indexName) > 0) {
            return;
        }

        $this->httpPut(\sprintf('/%s/my_type/%s', $indexName, uniqid()), [
            'first_name' => 'Cedric',
            'nick_name' => 'Skwi',
            'age' => 33,
        ]);

        $this->assert->integer($this->countIndexInsertion($indexName))->isGreaterThan(0);
    }

    /**
     * @Then the index named :indexName is new
     */
    public function theIndexNamedIsNew(string $indexName)
    {
        $this->assert->integer($this->countIndexInsertion($indexName))->isZero();
    }

    /**
     * @When I reload the index named :indexName
     */
    public function iReloadTheIndexNamed(string $indexName)
    {
        $this->getIndex($indexName)->reload();
    }

    /**
     * @Then the index named :indexName is empty
     */
    public function theIndexNamedIsEmpty(string $indexName)
    {
        throw new PendingException();
    }

    /**
     * @When I add objects of type :objectType with data to index :indexName :
     */
    public function iAddObjectsOfTypeWithDataToIndex($objectType, $indexName, TableNode $objectList)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));

        $objectListHash = $objectList->getHash();
        foreach ($objectListHash as $objectListRow) {
            $objectId = $objectListRow['id'];
            $objectIndexer->index(new IndexableObject($objectId, $objectListRow), $objectType);
        }
    }

    /**
     * @Then the object of type :objectType indexed in :indexName with id :id has data :
     */
    public function theObjectOfTypeIndexedInWithIdHasData($objectType, $indexName, $id, TableNode $expectedDataList)
    {
        $objectInfo = $this->httpGet(sprintf('/%s/%s/%s', $indexName, $objectType, $id));
        $expectedData = $expectedDataList->getHash()[0];

        foreach ($expectedDataList->getRow(0) as $key) {
            $this->assert->string($objectInfo['_source'][$key])->isEqualTo($expectedData[$key]);
        }
    }

    /**
     * @When I delete the object with id :id of type :objectType indexed in :indexName
     */
    public function iDeleteTheObjectWithIdOfTypeIndexedIn($id, $objectType, $indexName)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));
        $objectIndexer->removeById($id, $objectType);
    }

    /**
     * @Then the object of type :objectType indexed in :indexName with id :id does not exist
     */
    public function theObjectOfTypeIndexedInWithIdDoesNotExist($objectType, $indexName, $id)
    {
        $this->assert->integer($this->httpGetStatus(sprintf('/%s/%s/%s', $indexName, $objectType, $id)))->isEqualTo(404);
    }

    /**
     * @Then todo
     */
    public function todo()
    {
        throw new PendingException();
    }

    /**
     * @param string|null $indexName
     * @param array|null $config
     * @return Index
     * @throws \Exception
     */
    private function getIndex(string $indexName, array $config = null): Index
    {
        return new Index(['127.0.0.1:9200'], $indexName, $config ?? $this->defaultConfiguration);
    }

    /**
     * @param string $indexName
     * @return int
     */
    private function countIndexInsertion(string $indexName): int
    {
        $response = $this->httpGet(\sprintf('/%s/_stats', $indexName));
        return $response['_all']['primaries']['indexing']['index_total'] ?? 0;
    }

    /**
     * @param string $uri
     * @return int
     */
    private function httpGetStatus(string $uri): int
    {
        $response = $this->httpCall('GET', $uri);

        return $response['status'];
    }

    /**
     * @param string $uri
     * @return array
     */
    private function httpGet(string $uri): array
    {
        $response = $this->httpCall('GET', $uri);

        return json_decode(stream_get_contents($response['body']), true);
    }

    /**
     * @param string $uri
     * @throws \Exception
     */
    private function httpDelete(string $uri)
    {
        $response = $this->httpCall('DELETE', $uri);
        if ($response['status'] === 200) {
            return;
        }

        throw new \Exception('Index has not been deleted');
    }

    /**
     * @param string $uri
     * @throws \Exception
     */
    private function httpPut(string $uri, array $data)
    {
        $response = $this->httpCall('PUT', $uri, $data);
        if ($response['status'] !== 201) {
            throw new \Exception('Error putting data onto server');
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $data
     * @return \GuzzleHttp\Ring\Future\CompletedFutureArray
     */
    private function httpCall(string $method, string $uri, array $data = null)
    {
        $handler = new CurlHandler();
        $request = [
            'http_method' => $method,
            'uri' => $uri,
            'headers' => ['host' => ['127.0.0.1:9200']],
            'body' => json_encode($data),
            'future' => false,
        ];

        return $handler($request);
    }
}
