<?php


namespace Novaway\ElasticsearchClient\Query;


class BoolQuery implements Query
{

    /** @var string */
    private $combiningFactor;
    /** @var Query[] */
    private $queries;

    public function __construct(string $combiningFactor = CombiningFactor::MUST)
    {
        $this->combiningFactor = $combiningFactor;
        $this->queries = [];
    }

    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }



    public function addQuery(Query $query)
    {
        $this->queries[] = $query;
    }

    public function formatForQuery(): array
    {
        $res = [];
        foreach ($this->queries as $query) {
            $res[$query->getCombiningFactor()][] = $query->formatForQuery();
        }

        return ['bool' => $res];
    }


}
