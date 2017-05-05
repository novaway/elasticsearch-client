<?php

namespace Novaway\ElasticsearchClient\Query;

class Result
{
    /** @var int */
    private $totalHits;

    /** @var array */
    private $hits;

    /**
     * Result constructor.
     *
     * @param integer $totalHits
     * @param array $hits
     */
    public function __construct($totalHits, $hits)
    {
        $this->hits = $hits;
        $this->totalHits = $totalHits;
    }

    /**
     * @param array $arrayResult
     * @param ResultTransformer|null $resultTransformer
     * @return Result
     */
    public static function createFromArray(array $arrayResult): self
    {
        $totalHits = isset($arrayResult['hits']['total']) ? $arrayResult['hits']['total'] : 0;
        $hits = isset($arrayResult['hits']['hits']) ? array_map(function ($hit) {
            if (isset($hit['_source'])) {
                return $hit['_source'];
            }
        }, $arrayResult['hits']['hits']) : [];

        return new self($totalHits, $hits);
    }

    /**
     * @return int
     */
    public function totalHits()
    {
        return $this->totalHits;
    }

    /**
     * @return array
     */
    public function hits()
    {
        return $this->hits;
    }
}
