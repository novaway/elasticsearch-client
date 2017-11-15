<?php

namespace Novaway\ElasticsearchClient\Filter;

interface Filter
{
    /**
     * Return filter configuration for query
     *
     * @return array
     */
    public function formatForQuery(): array;
}
