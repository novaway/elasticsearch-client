<?php


namespace Novaway\ElasticsearchClient\Query;


class MatchAllQuery implements Query
{

    public function getCombiningFactor(): string
    {
        return CombiningFactor::MUST;
    }

    public function formatForQuery(): array
    {
        return [
            'match_all' => []
        ];
    }
}
