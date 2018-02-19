<?php

namespace Test\Functional\Novaway\ElasticsearchClient\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Ring\Client\CurlHandler;
use mageekguy\atoum\asserter\generator as AssertGenerator;
use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Filter\ExistsFilter;
use Novaway\ElasticsearchClient\Filter\GeoDistanceFilter;
use Novaway\ElasticsearchClient\Filter\InArrayFilter;
use Novaway\ElasticsearchClient\Filter\NestedFilter;
use Novaway\ElasticsearchClient\Filter\RangeFilter;
use Novaway\ElasticsearchClient\Filter\TermFilter;
use Novaway\ElasticsearchClient\Index;
use Novaway\ElasticsearchClient\ObjectIndexer;
use Novaway\ElasticsearchClient\Query\BoostableField;
use Novaway\ElasticsearchClient\Query\MultiMatchQuery;
use Novaway\ElasticsearchClient\Query\PrefixQuery;
use Novaway\ElasticsearchClient\Query\QueryBuilder;
use Novaway\ElasticsearchClient\Query\Result;
use Novaway\ElasticsearchClient\Query\BoolQuery;
use Novaway\ElasticsearchClient\Query\MatchQuery;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\QueryExecutor;
use Novaway\ElasticsearchClient\Score\DecayFunctionScore;
use Novaway\ElasticsearchClient\Score\RandomScore;
use Symfony\Component\Yaml\Yaml;
use Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos\IndexableObject;


/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var AssertGenerator */
    private $assert;
    /** @var Index[] */
    private $indexes;
    /** @var array */
    private $defaultConfiguration;
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var Result */
    private $result;

    /**
     * FeatureContext constructor.
     */
    public function __construct()
    {
        $this->assert = new AssertGenerator();
        $this->defaultConfiguration = Yaml::parse(file_get_contents(__DIR__ . '/data/config.yml'));
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
        $config = Yaml::parse(file_get_contents(__DIR__ . '/' . $configFile));
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
            if ($mappingDataRow['analyzer']) {
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
            'first_name' => 'Barry',
            'nick_name' => 'Flash',
            'age' => 32,
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
     * @When I add objects of type :objectType to index :indexName with data :
     */
    public function iAddObjectsOfTypeToIndexWithData($objectType, $indexName, TableNode $objectList)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));

        $objectListHash = $objectList->getHash();
        foreach ($objectListHash as $objectListRow) {
            $indexableObject = new IndexableObject($objectListRow['id'], $objectListRow);
            $objectIndexer->index($indexableObject, $objectType);
        }

        sleep(1);
    }

    /**
     * @When I update object of type :objectType with id :id in index :indexName with data :
     */
    public function iUpdateObjectOfTypeWithIdInIndexWithData($objectType, $id, $indexName, TableNode $objectList)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));
        $newData = $objectList->getHash()[0];

        $objectIndexer->index(new IndexableObject($id, $newData), $objectType);
    }

    /**
     * @When I create geo objects of type "my_geo_type" to index :indexName
     */
    public function iCreateObjectsOfTypeMyGeoTypeToIndex($indexName)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));

        $cityArray =
            [
                ['id' => 1, 'city_name' => 'lyon', 'location' => ['lat' => '45.764043', 'lon' => '4.835658999999964' ]],
                ['id' => 2, 'city_name' => 'paris', 'location' => ['lat' => '48.85661400000001', 'lon' => '2.3522219000000177' ]],
                ['id' => 3, 'city_name' => 'mâcon', 'location' => ['lat' => '46.30688389999999', 'lon' => '4.828731000000062' ]]
            ];

        foreach ($cityArray as $cityRow) {
            $indexableObject = new IndexableObject($cityRow['id'], $cityRow);
            $objectIndexer->index($indexableObject, 'my_geo_type');
        }

        sleep(1);
    }

    /**
     * @When I create nested index and populate it on :indexName
     */
    public function iCreateObjectsOfNestedTypeToIndex($indexName)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));

        $cityArray =
            [
                ['id' => 1, 'title' => 'Incredible Hulk', 'authors' => [
                    ['first_name' => 'Jack', 'last_name' => 'Kirby'] ,
                    ['first_name' => 'Stan', 'last_name' => 'Lee' ]
                ]],
            ];

        foreach ($cityArray as $cityRow) {
            $indexableObject = new IndexableObject($cityRow['id'], $cityRow);
            $objectIndexer->index($indexableObject, 'nested_type');
        }

        sleep(1);
    }

    /**
     * @Given I search cities with a coordinate :coordinate at :distance :unit
     */
    public function iSearchCitiesWithACoordinateAtDistance($coordinate, $distance, $unit)
    {
        $arrayCoordinate = explode(',', $coordinate);
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->addFilter(new GeoDistanceFilter('location', $arrayCoordinate[0], $arrayCoordinate[1], $distance, CombiningFactor::FILTER, $unit));
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
     * @Given I build a query matching :
     */
    public function iBuildAQueryMatching(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $this->queryBuilder->match($queryRow['field'], $queryRow['value'], $queryRow['condition']);
        }
    }

    /**
     * @Given I build the query with filter :
     * @Given I build a query with filter :
     */
    public function iBuildAQueryWithFilter(TableNode $filterTable)
    {
        $typeClasses = [
            'term' => TermFilter::class,
            'in_array' => InArrayFilter::class,
            'range' => RangeFilter::class,
            'exists' => ExistsFilter::class,
        ];

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();

        $filterHash = $filterTable->getHash();
        foreach ($filterHash as $filterRow) {

            $typeClass = $typeClasses[$filterRow['type']];
            unset($filterRow['type']);

            if($typeClass === InArrayFilter::class) {
                $filterRow['value'] = explode(';', $filterRow['value']);
            }

            $this->queryBuilder->addFilter(new $typeClass(...array_values($filterRow)));
        }
    }

    /**
     * @Given I build the query with female post filter
     */
    public function iBuildAQueryWithFemalePostFilter()
    {

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->setPostFilter(new TermFilter('gender', 'female'));
    }

    /**
     * @Given I build the query with female and over 30 post filter
     */
    public function iBuildAQueryWithFemaleAndOver30PostFilter()
    {

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $bool = new BoolQuery();
        $bool->addClause(new TermFilter('gender', 'female'));
        $bool->addClause(new RangeFilter('age', 30, RangeFilter::GREATER_THAN_OR_EQUAL_OPERATOR));

        $this->queryBuilder->setPostFilter($bool);
    }
    /**
     * @When I execute it on the index named :indexName for type :objectType
     */
    public function iExecuteItOnTheIndexNamed($indexName, $objectType)
    {
        $queryExecutor = new QueryExecutor($this->getIndex($indexName));
        $this->result = $queryExecutor->execute($this->queryBuilder->getQueryBody(), $objectType);
    }

    /**
     * @Then the result should contain exactly ids :idList
     */
    public function theResultShouldContainExactlyIds($idList)
    {
        $foundCount = 0;

        foreach ($this->result->hits() as $hit) {
            $foundCount += in_array($hit['id'], $idList) ? 1 : 0;
        }

        $this->assert->integer($this->result->totalHits())->isEqualTo(count($idList));
        $this->assert->integer($foundCount)->isEqualTo(count($idList));
    }

    /**
     * @Then the result should contain only ids :idList
     */
    public function theResultShouldContainOnlyIds($idList)
    {
        $foundCount = 0;

        foreach ($this->result->hits() as $hit) {
            $foundCount += in_array($hit['id'], $idList) ? 1 : 0;
        }

        $this->assert->integer($foundCount)->isEqualTo(count($idList));
    }

    /**
     * @Then the result should contain :totalCount hits
     */
    public function theResultShouldContainHits($totalCount)
    {
        $this->assert->integer($this->result->totalHits())->isEqualTo($totalCount);
    }

    /**
     * @Given I set query offset to :offset and limit to :limit
     */
    public function iSetQueryOffsetToAndLimitTo($offset, $limit)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->setLimit($limit);
        $this->queryBuilder->setOffset($offset);
    }

    /**
     * @Given I set query minimum score to :min
     */
    public function iSetQueryMinimumScoreTo($min)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->setMinimumScore($min);
    }

    /**
     * @Given I set highlight tags to :preTags and :postTags for :field
     */
    public function iSetHilghlightTagsTo($preTags, $postTags, $field)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->setHighlightTags($field, [$preTags], [$postTags]);
    }

    /**
     * @Then the result with the id :id should contain :highlight in :field
     */
    public function theResultShouldContainHighlight($id, $highlight, $field)
    {
        foreach ($this->result->hits() as $hit) {
            if ((int)$hit['id'] == $id && $hit[$field] == $highlight) {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * @Given I build the query with aggregation :
     * @Given I build a query with aggregation :
     */
    public function iBuildAQueryWithAggregation(TableNode $aggregationTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $aggregationHash = $aggregationTable->getHash();
        foreach ($aggregationHash as $aggregationRow) {
            $this->queryBuilder->addAggregation(new Aggregation($aggregationRow['name'], $aggregationRow['category'], $aggregationRow['field']));
        }
    }

    /**
     * @Then the result for aggregation :name should contain :value
     */
    public function theScalarResultShouldContain($name, $value)
    {
        $this->assert->float($this->result->aggregations()[$name])->isEqualTo($value);
    }

    /**
     * @Then the bucket result for aggregation :name should contain :count result for :value
     */
    public function theBucketResultShouldContain($name, $value, $count)
    {
        foreach ($this->result->aggregations()[$name] as $key => $bucket) {
            if ($bucket['key'] == $value) {
                $this->assert->integer($bucket['doc_count'])->isEqualTo($count);
                return true;
            }
        }
        throw new \Exception("No result found for $value");
    }

    /**
     * @Given I build a :combining bool query with :
     */
    public function iBuildABoolQueryWithQueries($combining, TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $boolQuery = new BoolQuery($combining);
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            if ( $queryRow['condition'] == CombiningFactor::FILTER) {
                $boolQuery->addClause(new TermFilter($queryRow['field'], $queryRow['value']));
            } else {
                $boolQuery->addClause(new MatchQuery($queryRow['field'], $queryRow['value'], $queryRow['condition']));
            }

        }
        $this->queryBuilder->addQuery($boolQuery);
    }
    /**
     * @When I add a random score with :seed as seed
     */
    public function iAddARandomSortWithSeed(string $seed)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->addFunctionScore(new RandomScore($seed));
    }

    /**
     * @Given I build a :function decay function with :
     */
    public function iBuildADecayFunction($function, TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $this->queryBuilder->addFunctionScore(new DecayFunctionScore(
                $queryRow['field'],
                $function,
                $queryRow['origin'],
                $queryRow['offset'],
                $queryRow['scale']
                ));
        }
    }

    /**
     * @Given I build a nested filter on :property with filters
     */
    public function iBuildANestedFilterOnPathWithFilters($property, TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $nestedFilter = new NestedFilter($property);
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $nestedFilter->addClause(new TermFilter($queryRow['field'], $queryRow['value']));
        }
        $this->queryBuilder->addFilter($nestedFilter);
    }

    /**
     * @Given I build a :combining multi match query with :type searching :query, and :operator operator with these fields
     */
    public function iBuildAMultiMatchQuery(string $combining, string $type, string $query, string $operator, TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $fields = [];

        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $fields[] = new BoostableField($queryRow['field'], $queryRow['boost']);
        }
        $this->queryBuilder->addQuery(new MultiMatchQuery($query, $fields, $combining, ['type' => $type, 'operator' => $operator]));
    }

    /**
     * @Given I build a prefix query matching :
     */
    public function iBuildAPrefixQueryMatching(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $this->queryBuilder->addQuery(new PrefixQuery($queryRow['field'], $queryRow['value'], $queryRow['condition']));
        }
    }


    /**
     * @Then todo
     */
    public function todo()
    {
        throw new PendingException();
    }

    /**
     * @Transform /^\[(.*)\]$/
     */
    public function castStringToArray($string)
    {
        return explode(';', $string);
    }

    /**
     * @param string|null $indexName
     * @param array|null $config
     * @return Index
     * @throws \Exception
     */
    private function getIndex(string $indexName, array $config = null): Index
    {
        if (!isset($this->indexes[$indexName])) {
            $this->indexes[$indexName] = new Index(['127.0.0.1:9200'], $indexName, $config ?? $this->defaultConfiguration);
        }

        return $this->indexes[$indexName];
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
