<?php

namespace Novaway\ElasticsearchClient\Query;

use Novaway\ElasticsearchClient\Filter\FilterInterface;
use Novaway\ElasticsearchClient\FunctionScore\FunctionScoreInterface;

class QueryBuilder
{
    const DEFAUT_OFFSET = 0;
    const DEFAUT_LIMIT = 10;
    const DEFAUT_MIN_SCORE = 0.01;

    const FILTER_CONDITION_MUST = 'must';
    const FILTER_CONDITION_SHOULD = 'should';
    const FILTER_MUST_NOT = 'must_not';

    /** @var array */
    private $queryBody;

    /** @var FunctionScoreInterface[] */
    private $functionScoreCollection;

    /** @var FilterInterface[] */
    private $filterCollection;

    /** @var MatchQuery[] */
    private $matchCollection;

    /**
     * QueryBuilder constructor.
     */
    public function __construct($offset = self::DEFAUT_OFFSET, $limit = self::DEFAUT_LIMIT, $minScore = self::DEFAUT_MIN_SCORE)
    {
        $this->queryBody               = [];
        $this->functionScoreCollection = [];
        $this->filterCollection        = [];
        $this->matchCollection         = [];

        $this->queryBody['from']      = $offset;
        $this->queryBody['size']      = $limit;
        $this->queryBody['min_score'] = $minScore;
    }

    /**
     * @param Index $index
     *
     * @return QueryBuilder
     */
    public static function createNew($offset = self::DEFAUT_OFFSET, $limit = self::DEFAUT_LIMIT, $minScore = self::DEFAUT_MIN_SCORE)
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
     * @param $field
     * @param $value
     *
     * @return QueryBuilder
     */
    public function match($field, $value, $combiningFactor = CombiningFactor::SHOULD): QueryBuilder
    {
        $this->matchCollection[] = new MatchQuery($field, $value, $combiningFactor);

        return $this;
    }

    /**
     * @param FunctionScoreInterface $functionScore
     * @param string                 $nestedTo
     *
     * @return QueryBuilder
     */
    public function addFunctionScore(FunctionScoreInterface $functionScore): QueryBuilder
    {
        $this->functionScoreCollection[] = $functionScore->formatForQuery();

        return $this;
    }

    /**
     * @param FilterInterface $filter
     * @param string          $condition (should or must)
     *
     * @return QueryBuilder
     */
    public function addFilter(FilterInterface $filter, $condition = self::FILTER_CONDITION_MUST): QueryBuilder
    {
        if (!in_array($condition, [self::FILTER_CONDITION_MUST, self::FILTER_CONDITION_SHOULD])) {
            throw new \InvalidArgumentException('Filter conditions should either be "should" or "must"');
        }

        $this->filterCollection[$condition][] = $filter->formatForQuery();

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryBody(): array
    {
        if (count($this->functionScoreCollection)) {
            $this->queryBody['query']['function_score']['functions'] = $this->functionScoreCollection;
        }

        if (count($this->filterCollection)) {
            $this->queryBody['filter']['bool'] = $this->filterCollection;
        }

        foreach ($this->matchCollection as $match) {
            $this->queryBody['query']['bool'][$match->getCombiningFactor()][] = ['match' => [$match->getField() => $match->getValue()]];
        }

        return $this->queryBody;
    }
}
