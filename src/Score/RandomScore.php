<?php

namespace Novaway\ElasticsearchClient\Score;

use Novaway\ElasticsearchClient\Query\Compound\FunctionScore as FunctionScore;

class RandomScore implements FunctionScore
{
    /** @var string */
    private $seed;

    public function __construct(string $seed)
    {
        $this->seed = $seed;
    }

    public function formatForQuery(): array
    {
        return [
            'random_score' => [
                'seed' => $this->seed
            ]
        ];
    }
}
