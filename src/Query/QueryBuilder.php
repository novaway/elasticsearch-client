<?php

namespace Novaway\ElasticsearchClient\Query;

use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Clause;
use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Score\FunctionScore;
use Novaway\ElasticsearchClient\Script\ScriptField;
use Novaway\ElasticsearchClient\Script\ScriptScore;

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
    /** @var Aggregation[]  */
    protected $aggregationCollection;
    /** @var FunctionScore[] */
    protected $functionScoreCollection;
    /** @var ScriptField[] */
    protected $scriptFieldCollection;
    /** @var null|ScriptScore */
    protected $scriptScore;
    /**
     * With script fields, by default _source is removed in the response
     * which as it creates a behaviour discrepency
     * therefore, we add a scripted_field retrieving _source by default.
     * This boolean allows sto remove that scripted_field in case it is
     *
     * @var bool
     */
    protected $sourceInScriptFields;

    public function __construct($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_LIMIT, $minScore = self::DEFAULT_MIN_SCORE)
    {
        $this->queryBody = [];
        $this->filterCollection = [];
        $this->matchCollection = [];
        $this->queryCollection = [];
        $this->aggregationCollection = [];
        $this->functionScoreCollection = [];
        $this->scriptFieldCollection = [];
        $this->sourceInScriptFields = true;

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
        $this->queryBody['sort'][] = [$field => [ 'order' => $order]];

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

    public function deactivateSourceInScriptFields()
    {
        $this->sourceInScriptFields = false;
    }

    /**
     * @return array
     */
    public function getQueryBody(): array
    {
        if (count($this->queryCollection) === 0) {
            $queryBody['query']['bool'][CombiningFactor::MUST]['match_all'] = new \stdClass();
        }
        foreach ($this->getClauseCollection() as $clause) {
            $queryBody['query']['bool'][$clause->getCombiningFactor()][] = $clause->formatForQuery();
        }

        foreach ($this->getScriptFieldCollection() as $script) {
            if ($this->sourceInScriptFields && !isset($queryBody['script_fields']['_source'])) {
                // when a script_field is added, the _source field is not returned in the $hit
                // which is annoying as it creates a behaviour discrepency
                // therefore, we add a scripted_field retrieving _source by default, deactivable
                $queryBody['script_fields']['_source'] =  ['script' => "params._source"];
            }
            $queryBody['script_fields'][$script->getField()] = $script->formatForQuery();
        }

        if (!empty($this->functionScoreCollection)) {
            $query = $queryBody['query'];
            unset($queryBody['query']['bool']);

            $function = ['query' => $query];

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

        return array_merge($this->queryBody, $queryBody);
    }
}
