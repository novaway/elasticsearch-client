<?php

namespace Novaway\ElasticsearchClient\Query;

use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Clause;
use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\Compound\FunctionScore;
use Novaway\ElasticsearchClient\Query\FullText\MatchQuery;
use Novaway\ElasticsearchClient\Score\FunctionScoreOptions;
use Novaway\ElasticsearchClient\Score\ScriptScore;
use Novaway\ElasticsearchClient\Script\ScriptField;

class QueryBuilder
{
    const DEFAULT_OFFSET = 0;
    const DEFAULT_LIMIT = 10;
    const DEFAULT_MIN_SCORE = 0.01;

    /** @var array */
    protected $queryBody;

    /**
     * @var Filter[]
     * @deprecated
     */
    protected $filterCollection;
    /** @var Clause */
    protected $postFilter;
    /**
     * @var MatchQuery[]
     * @deprecated
     */
    protected $matchCollection;
    /** @var Query[] */
    protected $queryCollection;
    /** @var Aggregation[]  */
    protected $aggregationCollection;
    /** @var FunctionScore[] */
    protected $functionScoreCollection;
    /** @var ScriptField[] */
    protected $scriptFieldCollection;
    /** @var null|ScriptScore */
    protected $scriptScore;
    /** @var null|FunctionScoreOptions */
    protected $functionsScoreOptions;
    /**
     * @var array
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-search-after.html
     */
    protected $searchAfter;

    public function __construct($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_LIMIT, $minScore = self::DEFAULT_MIN_SCORE)
    {
        $this->queryBody = [];
        $this->filterCollection = [];
        $this->matchCollection = [];
        $this->queryCollection = [];
        $this->aggregationCollection = [];
        $this->functionScoreCollection = [];
        $this->scriptFieldCollection = [];
        $this->searchAfter = [];

        $this->queryBody['from'] = $offset;
        $this->queryBody['size'] = $limit;
        $this->queryBody['min_score'] = $minScore;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param float $minScore
     *
     * @return QueryBuilder
     */
    public static function createNew($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_LIMIT, $minScore = self::DEFAULT_MIN_SCORE): QueryBuilder
    {
        return new self($offset, $limit, $minScore);
    }

    /**
     * @param integer $offset
     *
     * @return QueryBuilder
     */
    public function setOffset($offset): QueryBuilder
    {
        $this->queryBody['from'] = $offset;

        return $this;
    }

    /**
     * @param integer $limit
     *
     * @return QueryBuilder
     */
    public function setLimit($limit): QueryBuilder
    {
        $this->queryBody['size'] = $limit;

        return $this;
    }

    /**
     * @param $minScore
     *
     * @return QueryBuilder
     */
    public function setMinimumScore($minScore): QueryBuilder
    {
        $this->queryBody['min_score'] = $minScore;

        return $this;
    }

    public function addSort(string $field,string $order): QueryBuilder
    {
        $this->queryBody['sort'][] = [$field => [ 'order' => $order]];

        return $this;
    }

    /**
     * Reset sort value, and set it at the new value
     * @param string $field
     * @param string $order
     * @return QueryBuilder
     */
    public function setSort(string $field, string $order): QueryBuilder
    {
        unset($this->queryBody['sort']);

        return $this->addSort($field, $order);
    }

    /**
     * @param string $field
     * @param array $preTags
     * @param array $postTags
     * @return QueryBuilder
     */
    public function setHighlightTags(string $field, array $preTags, array $postTags): QueryBuilder
    {
        $this->queryBody['highlight']['fields'][] = [
            $field => [
                "pre_tags" => $preTags,
                "post_tags" => $postTags
            ]
        ];

        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @param string $combiningFactor
     *
     * @return QueryBuilder
     *
     * @deprecated Use MatchQuery instead
     */
    public function match($field, $value, $combiningFactor = CombiningFactor::SHOULD): QueryBuilder
    {
        if (!in_array($combiningFactor, [CombiningFactor::SHOULD, CombiningFactor::MUST, CombiningFactor::MUST_NOT])) {
            throw new \InvalidArgumentException('Match queries should either be combined by "should", "must" or "must_not"');
        }

        $this->queryCollection[] = new MatchQuery($field, $value, $combiningFactor);

        return $this;
    }

    /**
     * @param Filter $filter
     *
     * @return QueryBuilder
     * @deprecated Use addQuery instead
     */
    public function addFilter(Filter $filter): QueryBuilder
    {
        $this->filterCollection[] = $filter;

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return QueryBuilder
     * @deprecated Use addQuery instead
     */
    public function setFilters(array $filters): QueryBuilder
    {
        $this->filterCollection = $filters;

        return $this;
    }

    public function addAggregation(Aggregation $aggregation): QueryBuilder
    {
        $this->aggregationCollection[] = $aggregation;

        return $this;
    }

    public function addQuery(Query $query): QueryBuilder
    {
        $this->queryCollection[] = $query;

        return $this;
    }

    public function addFunctionScore(FunctionScore $functionScore): QueryBuilder
    {
        $this->functionScoreCollection[] = $functionScore;

        return $this;
    }

    /**
     * @return Clause[]
     */
    public function getClauseCollection()
    {
        return array_merge($this->queryCollection, $this->filterCollection, $this->matchCollection);
    }

    public function setPostFilter(Clause $clause): QueryBuilder
    {
        $this->postFilter = $clause;

        return $this;
    }

    public function addScriptField(ScriptField $scriptField)
    {
        $this->scriptFieldCollection[] = $scriptField;
    }

    /**
     * @return ScriptField[]
     */
    public function getScriptFieldCollection(): array
    {
        return $this->scriptFieldCollection;
    }

    /**
     * @return null|FunctionScoreOptions
     */
    public function getFunctionsScoreOptions()
    {
        return $this->functionsScoreOptions;
    }

    public function setFunctionsScoreOptions(FunctionScoreOptions $functionsScoreOptions)
    {
        $this->functionsScoreOptions = $functionsScoreOptions;
    }

    public function getSearchAfter(): array
    {
        return $this->searchAfter;
    }

    public function setSearchAfter(array $searchAfter)
    {
        $this->searchAfter = $searchAfter;
    }

    public function hasNoMatchingQueries(): bool
    {
        $nonFilterQueries = array_filter($this->getClauseCollection(), function (Clause $clause) {
            return !in_array($clause->getCombiningFactor(), [CombiningFactor::FILTER, CombiningFactor::MUST_NOT]);
        });
        return empty($nonFilterQueries);
    }

    /**
     * @return array
     */
    public function getQueryBody(): array
    {
        $queryBody['_source'] = [];

        if ($this->hasNoMatchingQueries()) {
            $queryBody['query']['bool'][CombiningFactor::MUST]['match_all'] = new \stdClass();
        }
        foreach ($this->getClauseCollection() as $clause) {
            $queryBody['query']['bool'][$clause->getCombiningFactor()][] = $clause->formatForQuery();
        }

        foreach ($this->getScriptFieldCollection() as $script) {
            $queryBody['script_fields'][$script->getField()] = $script->formatForQuery();
        }

        if (!empty($this->functionScoreCollection)) {
            $query = $queryBody['query'];
            unset($queryBody['query']['bool']);

            $function = $this->getFunctionsScoreOptions() ? $this->getFunctionsScoreOptions()->formatForQuery() : [];

            $function += ['query' => $query];

            foreach ($this->functionScoreCollection as $functionScore) {
                $function['functions'][] = $functionScore->formatForQuery();
            }
            $queryBody['query']['function_score'] = $function;
        }

        foreach ($this->aggregationCollection as $agg) {
            $queryBody['aggregations'][$agg->getName()][$agg->getCategory()] = $agg->getParameters();
        }

        if ($this->postFilter) {
            $queryBody['post_filter'] = $this->postFilter->formatForQuery();
        }

        if ($this->searchAfter) {
            $queryBody['search_after'] = $this->searchAfter;
        }

        return array_merge($this->queryBody, $queryBody);
    }
}
