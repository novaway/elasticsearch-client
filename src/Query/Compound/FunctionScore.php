<?php

namespace Novaway\ElasticsearchClient\Query\Compound;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
 */
interface FunctionScore
{
    /**
     * Return a JSON formatted representation of the clause, tu use in elasticsearch
     *
     * @return array
     */
    public function formatForQuery(): array;
}
