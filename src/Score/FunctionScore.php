<?php

namespace Novaway\ElasticsearchClient\Score;

interface FunctionScore
{
    /**
     * Return a JSON formatted representation of the clause, tu use in elasticsearch
     *
     * @return array
     */
    public function formatForQuery(): array;
}
