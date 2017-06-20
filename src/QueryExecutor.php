<?php

namespace Novaway\ElasticsearchClient;

use Novaway\ElasticsearchClient\Query\QueryBuilder;
use Novaway\ElasticsearchClient\Query\Result;
use Novaway\ElasticsearchClient\Query\ResultTransformer;

class QueryExecutor
{
    /** @var Index */
    private $index;

    /**
     * ObjectIndexer constructor.
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $type
     * @return Result
     */
    public function execute(array $queryBody, string $type, ResultTransformer $resultTransformer = null): Result
    {
        return $this->index->search([
            'type' => $type,
            'body' => $queryBody
        ], $resultTransformer);
    }
}
