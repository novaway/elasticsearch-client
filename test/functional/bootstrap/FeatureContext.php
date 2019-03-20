<?php

namespace Test\Functional\Novaway\ElasticsearchClient\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Ring\Client\CurlHandler;
use mageekguy\atoum\asserter\generator as AssertGenerator;
use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Index;
use Novaway\ElasticsearchClient\ObjectIndexer;
use Novaway\ElasticsearchClient\Query\BoostableField;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Compound\BoolQuery;
use Novaway\ElasticsearchClient\Query\FullText\MatchQuery;
use Novaway\ElasticsearchClient\Query\FullText\MultiMatchQuery;
use Novaway\ElasticsearchClient\Query\Geo\GeoDistanceQuery;
use Novaway\ElasticsearchClient\Query\Geo\InlineGeoShapeQuery;
use Novaway\ElasticsearchClient\Query\Joining\NestedQuery;
use Novaway\ElasticsearchClient\Query\QueryBuilder;
use Novaway\ElasticsearchClient\Query\Result;
use Novaway\ElasticsearchClient\Query\Term\ExistsQuery;
use Novaway\ElasticsearchClient\Query\Term\InArrayQuery;
use Novaway\ElasticsearchClient\Query\Term\PrefixQuery;
use Novaway\ElasticsearchClient\Query\Term\RangeQuery;
use Novaway\ElasticsearchClient\Query\Term\TermQuery;
use Novaway\ElasticsearchClient\QueryExecutor;
use Novaway\ElasticsearchClient\Score\DecayFunctionScore;
use Novaway\ElasticsearchClient\Score\FunctionScoreOptions;
use Novaway\ElasticsearchClient\Score\RandomScore;
use Novaway\ElasticsearchClient\Score\ScriptScore;
use Novaway\ElasticsearchClient\Script\ScriptField;
use Symfony\Component\Yaml\Yaml;
use Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos\DesindexableObject;
use Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos\IndexableObject;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var AssertGenerator */
    private $assert;
    /** @var Index */
    private $index;
    /** @var array */
    private $defaultConfiguration;
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var Result */
    private $result;
    /** @var Client */
    private $client;

    /**
     * FeatureContext constructor.
     */
    public function __construct()
    {
        $this->assert = new AssertGenerator();
        $this->defaultConfiguration = Yaml::parse(file_get_contents(__DIR__ . '/data/config_my_index.yml'));
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

        $this->httpDelete(\sprintf('/%s/', $indexName));
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
     * @When I hotswap :indexName to tmp
     */
    public function iHotswapToTmp(string $indexName)
    {
        $this->getIndex($indexName)->hotswapToTmp();
    }

    /**
     * @When I hotswap :indexName to main
     */
    public function iHotswapToMain(string $indexName)
    {
        $this->getIndex($indexName)->hotswapToMain();
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
        $indexableObjects = [];
        foreach ($objectListHash as $objectListRow) {
            $indexableObjects[] = new IndexableObject($objectListRow['id'], $objectListRow);
        }
        $objectIndexer->bulkIndex($indexableObjects, $objectType);
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
     * @Then the object of type :objectType indexed in :indexName with id :id has data :
     */
    public function theObjectOfTypeIndexedInWithIdHasData($objectType, $indexName, $id, TableNode $expectedDataList)
    {
        $objectInfo = $this->httpGet(sprintf('/%s/%s/%s', $this->getIndex($indexName)->getSearchIndexName(), $objectType, $id));
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
     * @When I bulk delete the objects with ids :ids of type :objectType indexed in :indexName
     */
    public function iBulkDeleteTheObjectWithIdOfTypeIndexedIn($ids, $objectType, $indexName)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));
        $objects = [];
        foreach ($ids as $id) {
            $objects[] = new DesindexableObject($id, []);
        }
        $objectIndexer->bulkIndex($objects, $objectType);
    }

    /**
     * @Then the object of type :objectType indexed in :indexName with id :id exists
     */
    public function theObjectOfTypeIndexedInWithIdExists($objectType, $indexName, $id)
    {
        $this->assert->integer($this->httpGetStatus(sprintf('/%s/%s/%s', $indexName, $objectType, $id)))->isEqualTo(200);
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
     * @Given I add sorting on :
     */
    public function iAddSortingOn(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $this->queryBuilder->addSort($queryRow['field'], $queryRow['order']);
        }
    }

    /**
     * @Given I add search after :
     */
    public function iAddSearchAfter(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        $this->queryBuilder->setSearchAfter(array_column($queryHash,'sort'));
    }


    /**
     * @Given I build the query with filter :
     * @Given I build a query with filter :
     */
    public function iBuildAQueryWithFilter(TableNode $filterTable)
    {
        $typeClasses = [
            'term' => TermQuery::class,
            'in_array' => InArrayQuery::class,
            'range' => RangeQuery::class,
            'exists' => ExistsQuery::class,
        ];

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();

        $filterHash = $filterTable->getHash();
        foreach ($filterHash as $filterRow) {

            $typeClass = $typeClasses[$filterRow['type']];
            unset($filterRow['type']);

            if($typeClass === InArrayQuery::class) {
                $filterRow['value'] = explode(';', $filterRow['value']);
            }

            $this->queryBuilder->addQuery(new $typeClass(...array_values($filterRow)));
        }
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


        $this->assert->integer(\count($this->result->hits()))->isEqualTo(\count($idList));
        $this->assert->integer($foundCount)->isEqualTo(\count($idList));
    }

    /**
     * @Then the result n° :index should contain field :fieldName equaling :value
     */
    public function theNthResultShouldContainFieldEqualing(int $index, string $fieldName, string $value)
    {
        $nthHit = $this->result->hits()[$index];

        $this->assert->string((string)$nthHit[$fieldName])->isEqualTo($value);
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
                $boolQuery->addClause(new TermQuery($queryRow['field'], $queryRow['value']));
            } else {
                $boolQuery->addClause(new MatchQuery($queryRow['field'], $queryRow['value'], $queryRow['condition']));
            }

        }
        $this->queryBuilder->addQuery($boolQuery);
    }

    /**
     * @When I create geo objects of type :objectType to index :indexName
     */
    public function iCreateObjectsOfTypeMyGeoTypeToIndex(string $objectType, string $indexName)
    {
        $objectIndexer = new ObjectIndexer($this->getIndex($indexName));

        $cityArray =
            [
                ['id' => 1, 'city_name' => 'lyon', 'location' => ['lat' => '45.764043', 'lon' => '4.835658999999964' ], 'centerAsGeoshape' => 'POINT(4.835658999999964 45.764043)'],
                ['id' => 2, 'city_name' => 'paris', 'location' => ['lat' => '48.85661400000001', 'lon' => '2.3522219000000177' ], 'centerAsGeoshape' => 'POINT(2.3522219000000177 48.85661400000001)'],
                ['id' => 3, 'city_name' => 'mâcon', 'location' => ['lat' => '46.30688389999999', 'lon' => '4.828731000000062' ], 'centerAsGeoshape' => 'POINT(4.828731000000062 46.30688389999999)']
            ];

        foreach ($cityArray as $cityRow) {
            $indexableObject = new IndexableObject($cityRow['id'], $cityRow);
            $objectIndexer->index($indexableObject, $objectType);
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
        $this->queryBuilder->addQuery(new GeoDistanceQuery('location', $arrayCoordinate[0], $arrayCoordinate[1], $distance, CombiningFactor::FILTER, $unit));
    }

    /**
     * @Given I search cities with a relation :relation to rhône
     */
    public function iSearchCitiesWithARelationToRhone($relation)
    {

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $shape = json_decode(file_get_contents(__DIR__ . '/data/geoshapes/rhone.json'));

        $this->queryBuilder->addQuery(new InlineGeoShapeQuery('centerAsGeoshape', $shape, CombiningFactor::MUST, $relation));
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
     * @Given I build a script score function with :
     */
    public function iBuildAScriptScoreFunction(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $params =  isset($queryRow['params']) ?  json_decode($queryRow['params'], true) : [];
            $this->queryBuilder->addFunctionScore(new ScriptScore($queryRow['source'], $params, $queryRow['lang']));
        }
    }

    /**
     * @Given I set the function score options as :
     */
    public function iSetTheFunctionScoreOptionsAs(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        $queryRow = reset($queryHash);
        $this->queryBuilder->setFunctionsScoreOptions(new FunctionScoreOptions(
            $queryRow['scoreMode'] ?? null,
            $queryRow['boostMode'] ?? null,
            $queryRow['boost'] ?? null,
            $queryRow['maxBoost'] ?? null,
            $queryRow['minBoost'] ?? null
        ));
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
            $objectIndexer->index($indexableObject, '_doc');
        }

        sleep(1);
    }

    /**
     * @Given I build a nested filter on :property with filters
     */
    public function iBuildANestedFilterOnPathWithFilters($property, TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $nestedFilter = new NestedQuery($property);
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $nestedFilter->addClause(new TermQuery($queryRow['field'], $queryRow['value']));
        }
        $this->queryBuilder->addQuery($nestedFilter);
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
     * @Given I build the query with female post filter
     */
    public function iBuildAQueryWithFemalePostFilter()
    {

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $this->queryBuilder->setPostFilter(new TermQuery('gender', 'female'));
    }
    /**
     * @Given I build the query with female and over 30 post filter
     */
    public function iBuildAQueryWithFemaleAndOver30PostFilter()
    {

        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $bool = new BoolQuery();
        $bool->addClause(new TermQuery('gender', 'female'));
        $bool->addClause(new RangeQuery('age', 30, RangeQuery::GREATER_THAN_OR_EQUAL_OPERATOR));

        $this->queryBuilder->setPostFilter($bool);
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
     * @Given I build a script field matching :
     */
    public function iBuildAScriptFieldMatching(TableNode $queryTable)
    {
        $this->queryBuilder = $this->queryBuilder ?? QueryBuilder::createNew();
        $queryHash = $queryTable->getHash();
        foreach ($queryHash as $queryRow) {
            $params =  isset($queryRow['params']) ?  json_decode($queryRow['params'], true) : [];
            $this->queryBuilder->addScriptField(new ScriptField($queryRow['field'], $queryRow['source'], $params, $queryRow['lang']));
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
        if (!isset($this->index[$indexName])) {
            if (!isset($this->client)) {
                $builder = ClientBuilder::create()->setHosts(['127.0.0.1:9200']);
                $this->client = $builder->build();
            }
            $this->index[$indexName] = Index::createWithClient($this->client,  $indexName, $config ?? $this->defaultConfiguration);
        }

        return $this->index[$indexName];
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
            'future' => false,
        ];

        if($data) {
            $request['body'] =  json_encode($data);
        }

        return $handler($request);
    }

}
