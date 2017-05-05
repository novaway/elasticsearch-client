<?php

namespace Novaway\ElasticsearchClient\Filter;

interface FilterInterface
{
    /**
     * @param $params
     */
    public function __construct(array $params = []);

    /**
     * Return filter configuration for query
     *
     * @return array
     */
    public function formatForQuery();
}
