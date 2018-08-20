<?php

namespace Novaway\ElasticsearchClient\Query;

use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Clause;
use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Score\FunctionScore;

class QueryBuilder
{
    const DEFAULT_OFFSET = 0;
    const DEFAULT_LIMIT = 10;
    const DEFAULT_MIN_SCORE = 0.01;

    /** @var array */
    protected $queryBody;

    /** @var Filter[] */
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

    /** @var Aggregation[] */
    protected $aggregationCollection;

    /** @var FunctionScore[] */
    protected $functionScoreCollection;

    /** @var string $boostMode */
    protected $boostMode;


    public function __construct($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_LIMIT, $minScore = self::DEFAULT_MIN_SCORE, $boostMode = BoostMode::MULTIPLY)
    {
        $this->queryBody = [];
        $this->filterCollection = [];
        $this->matchCollection = [];
        $this->queryCollection = [];
        $this->aggregationCollection = [];
        $this->functionScoreCollection = [];
        $this->setBoostMode($boostMode);

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

    public function addSort($field, $order): QueryBuilder
    {
        $this->queryBody['sort'][] = [$field => ['order' => $order]];

        return $this;
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
     *
     * @return QueryBuilder
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

    public function setBoostMode(string $boostMode): QueryBuilder
    {
        if (!in_array($boostMode, [BoostMode::MULTIPLY, BoostMode::SUM, BoostMode::MIN, BoostMode::MAX, BoostMode::REPLACE])) {
            throw new \InvalidArgumentException(sprintf("function should be one of %s, %s, %s, %s, %s : %s given", BoostMode::MULTIPLY, BoostMode::SUM, BoostMode::MIN, BoostMode::MAX, BoostMode::REPLACE, $boostMode));
        }
        $this->boostMode = $boostMode;
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

    /**
     * @return array
     */
    public function getQueryBody(): array
    {
        if (count($this->queryCollection) === 0) {
            $queryBody['query']['bool'][CombiningFactor::MUST]['match_all'] = [];
        }
        foreach ($this->getClauseCollection() as $clause) {
            $queryBody['query']['bool'][$clause->getCombiningFactor()][] = $clause->formatForQuery();
        }

        if (!empty($this->functionScoreCollection)) {
            $query = $queryBody['query'];
            unset($queryBody['query']['bool']);

            $function = ['query' => $query];

            foreach ($this->functionScoreCollection as $functionScore) {
                $function['functions'][] = $functionScore->formatForQuery();
            }
            $queryBody['query']['function_score'] = $function;
            $queryBody['query']['function_score']['boost_mode'] = $this->boostMode;
        }

        foreach ($this->aggregationCollection as $agg) {
            $queryBody['aggregations'][$agg->getName()][$agg->getCategory()] = $agg->getParameters();
        }

        if ($this->postFilter) {
            $queryBody['post_filter'] = $this->postFilter->formatForQuery();
        }

        return array_merge($this->queryBody, $queryBody);
    }
}
