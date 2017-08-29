<?php

namespace Novaway\ElasticsearchClient\Query;

use Novaway\ElasticsearchClient\Filter\Filter;

class QueryBuilder
{
    const DEFAULT_OFFSET = 0;
    const DEFAULT_LIMIT = 10;
    const DEFAULT_MIN_SCORE = 0.01;

    /** @var array */
    protected $queryBody;

    /** @var Filter[] */
    protected $filterCollection;

    /** @var MatchQuery[] */
    protected $matchCollection;

    /**
     * QueryBuilder constructor.
     */
    public function __construct($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_LIMIT, $minScore = self::DEFAULT_MIN_SCORE)
    {
        $this->queryBody = [];
        $this->filterCollection = [];
        $this->matchCollection = [];

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

        $this->matchCollection[] = new MatchQuery($field, $value, $combiningFactor);

        return $this;
    }

    /**
     * @param Filter $filter
     *
     * @return QueryBuilder
     */
    public function addFilter(Filter $filter): QueryBuilder
    {
        $this->filterCollection[] = $filter->formatForQuery();

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return QueryBuilder
     */
    public function setFilters(array $filters): QueryBuilder
    {
        $this->filterCollection = array_map(function (Filter $filter) {
            return $filter->formatForQuery();
        }, $filters);

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryBody(): array
    {
        if (count($this->filterCollection)) {
            $this->queryBody['query']['bool']['filter'] = $this->filterCollection;
        }

        if (count($this->matchCollection) === 0) {
            $this->queryBody['query']['bool'][CombiningFactor::MUST]['match_all'] = [];
        }
        foreach ($this->matchCollection as $match) {
            $this->queryBody['query']['bool'][$match->getCombiningFactor()][] = ['match' => [$match->getField() => $match->getValue()]];
        }

        return $this->queryBody;
    }
}
