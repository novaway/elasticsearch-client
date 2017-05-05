<?php

namespace Novaway\ElasticsearchClient\FunctionScore;

interface FunctionScoreInterface
{
    /**
     * @param $params
     */
    public function __construct(array $params = []);

    /**
     * Return function configuration for query
     *
     * @return array
     */
    public function formatForQuery();
}
