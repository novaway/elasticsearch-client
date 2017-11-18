<?php


namespace Novaway\ElasticsearchClient\Query;


use Novaway\ElasticsearchClient\Clause;

class BoolQuery implements Query
{

    /** @var string */
    private $combiningFactor;
    /** @var Clause[] */
    private $clauses;

    public function __construct(string $combiningFactor = CombiningFactor::MUST)
    {
        $this->combiningFactor = $combiningFactor;
        $this->clauses = [];
    }

    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

    public function addClause(Clause $clause)
    {
        $this->clauses[] = $clause;
    }
    public function formatForQuery(): array
    {
        $res = [];
        foreach ($this->clauses as $clause) {
            $res[$clause->getCombiningFactor()][] = $clause->formatForQuery();
        }

        return ['bool' => $res];
    }


}
