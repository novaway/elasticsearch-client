<?php

namespace Novaway\ElasticsearchClient\Query;

class Result
{
    /** @var int */
    private $totalHits;

    /** @var array */
    private $hits;

    /** @var  array */
    private $aggregations;

    /**
     * Result constructor.
     *
     * @param integer $totalHits
     * @param array $hits
     */
    public function __construct(
        int $totalHits,
        array $hits,
        array $aggregations = []
    )
    {
        $this->hits = $hits;
        $this->totalHits = $totalHits;
        $this->aggregations = $aggregations;
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
                $hitFormated = $hit['_source'];
            }
            if (isset($hit['highlight'])) {
                foreach ($hit['highlight'] as $key => $highlight) {
                    $hitFormated[$key] = current($highlight);
                }
            }

            return $hitFormated;
        }, $arrayResult['hits']['hits']) : [];

        $aggregations = isset($arrayResult['aggregations']) ? array_map(function ($aggregation) {
            if (isset($aggregation['buckets'])) {
                // bucket aggregation
                return $aggregation['buckets'];
            }
            if (array_key_exists('value', $aggregation)) {
                // metric aggregation
                // in that case, array_key_exist is mandatory instead of isset,
                // as the result can legitimately be null
                return $aggregation['value'];
            }
            return $aggregation;
        }, $arrayResult['aggregations']) : [];

        return new self($totalHits, $hits, $aggregations);
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

    /**
     * @return array
     */
    public function aggregations()
    {
        return $this->aggregations;
    }
}
