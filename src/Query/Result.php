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
    /** @var int */
    private $limit;

    /**
     * Result constructor.
     *
     * @param integer $totalHits
     * @param array $hits
     * @param array $aggregations
     * @param int|null $limit
     */
    public function __construct(
        int $totalHits,
        array $hits,
        array $aggregations = [],
        int $limit = null
    )
    {
        $this->hits = $hits;
        $this->totalHits = $totalHits;
        $this->aggregations = $aggregations;
        $this->limit = $limit;
    }

    /**
     * @param array $arrayResult
     * @param int|null $limit
     * @return Result
     */
    public static function createFromArray(array $arrayResult, int $limit = null): self
    {
        $totalHits = isset($arrayResult['hits']['total']) ? $arrayResult['hits']['total'] : 0;

        $hits = [];
        if (isset($arrayResult['hits']['hits'])) {
            $hits = array_map(function ($hit) {
                $hitFormated = [];

                if (isset($hit['_source'])) {
                    $hitFormated = $hit['_source'];
                }

                $underscoreFields = [
                    '_id',
                    '_score',
                    '_type',
                    '_index'
                ];

                foreach ($underscoreFields as $field) {
                    if (isset($hit[$field])) {
                        $hitFormated[$field] = $hit[$field];
                    }
                }

                if (isset($hit['highlight'])) {
                    foreach ($hit['highlight'] as $key => $highlight) {
                        $hitFormated[$key] = current($highlight);
                    }
                }

                if (isset($hit['fields'])) {
                    foreach ($hit['fields'] as $key => $computedFields) {
                        $hitFormated[$key] = current($computedFields);
                    }
                }
                return $hitFormated;
            }, $arrayResult['hits']['hits']);
        };

        $aggregations = [];
        if (isset($arrayResult['aggregations'])) {
            $aggregations =  array_map(function ($aggregation) {
                if (isset($aggregation['buckets'])) {
                    // bucket aggregation
                    return $aggregation['buckets'];
                }
                if (array_key_exists('value', $aggregation)) {
                    // Single scalar metric aggregation
                    // in that case, array_key_exist is mandatory instead of isset,
                    // as the result can legitimately be null
                    return $aggregation['value'];
                }
                return $aggregation;
            }, $arrayResult['aggregations']);

        }

        return new self($totalHits, $hits, $aggregations, $limit);
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

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit(int $limit = null)
    {
        $this->limit = $limit;
    }

    /**
     * @param int $limit the number of hits per request
     * @return int
     */
    public function numberOfPages(): int
    {
        if ($this->limit <= 0) {
            throw new \InvalidArgumentException("limit parameter must be strictly positive, $this->limit given");
        }
        if (!is_int($this->totalHits)) {
            throw new \UnexpectedValueException("totalHits seems to be uninitialized, did you call 'numberOfPages' on an uninitialised Result ?");
        }
        return ceil($this->totalHits / $this->limit);
    }

}
