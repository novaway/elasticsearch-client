<?php

namespace Novaway\ElasticsearchClient\Query;

interface ResultTransformer
{
    /**
     * @param Result $result
     *
     * @return Result Formatted result
     */
    public function formatResult(Result $result): Result;
}
