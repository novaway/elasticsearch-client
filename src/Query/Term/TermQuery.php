<?php

namespace Novaway\ElasticsearchClient\Query\Term;

use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class TermQuery implements Filter, Query
{
    /** @var string */
    private $property;
    /** @var string */
    private $value;
    /** @var string */
    private $combiningFactor;
    /** @var float */
    private $boost;

    public function __construct(string $property, $value, string $combiningFactor = CombiningFactor::FILTER, float $boost = 1)
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        $this->property = $property;
        $this->value = $value;
        $this->combiningFactor = $combiningFactor;
        $this->boost = $boost;
    }

    /**
     * @return string
     */
    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery(): array
    {
        return [
            'term' => [
                $this->property =>  [
                    'value' => $this->value,
                    'boost' => $this->boost
                ]
            ]
        ];
    }
}

