<?php


namespace Novaway\ElasticsearchClient\Query;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html
 */
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
