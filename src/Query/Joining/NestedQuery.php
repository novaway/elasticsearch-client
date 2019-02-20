<?php


namespace Novaway\ElasticsearchClient\Query\Joining;


use Novaway\ElasticsearchClient\Clause;
use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class NestedQuery implements Filter, Query
{
    /** @var string */
    protected $property;
    /** @var Clause[] */
    protected $clauses;
    /** @var string */
    private $combiningFactor;

    public function __construct(string $property, $combiningFactor = CombiningFactor::FILTER)
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        $this->property = $property;
        $this->combiningFactor = $combiningFactor;
        $this->clauses = [];
    }

    public function addClause(Clause $clause)
    {
        $this->clauses[] = $clause;
    }

    public function formatForQuery(): array
    {
        return ['nested' => [
            'path' => $this->property,
            'query' => [
                'bool' => [
                    'filter' => array_map(function(Clause $clause) {
                        return $clause->formatForQuery();
                    }, $this->clauses)
                ]
            ]
        ]];
    }

    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

}
