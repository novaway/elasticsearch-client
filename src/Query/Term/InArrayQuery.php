<?php

namespace Novaway\ElasticsearchClient\Query\Term;

use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class InArrayQuery implements Filter, Query
{
    /** @var string */
    private $property;
    /** @var array */
    private $values;
    /** @var string */
    private $combiningFactor;

    public function __construct(string $property, array $values, string $combiningFactor = CombiningFactor::FILTER)
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        $this->property = $property;
        $this->values = $values;
        $this->combiningFactor = $combiningFactor;
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
            'bool' => [
                'should' => array_map(function ($value) {
                    $matchFilter = new TermQuery($this->property, $value);
                    return $matchFilter->formatForQuery();
                }, $this->values)
            ]
        ];
    }
}
