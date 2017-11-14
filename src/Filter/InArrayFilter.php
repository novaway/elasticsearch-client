<?php

namespace Novaway\ElasticsearchClient\Filter;

use Novaway\ElasticsearchClient\Query\CombiningFactor;

class InArrayFilter implements Filter
{
    /** @var string */
    private $property;
    /** @var array */
    private $values;
    /** @var string */
    private $combiningFactor;

    /**
     * InArrayFilter constructor.
     * @param string $property
     * @param array $values
     */
    public function __construct(string $property, array $values, string $combiningFactor = CombiningFactor::FILTER)
    {
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
                    $matchFilter = new TermFilter($this->property, $value);
                    return $matchFilter->formatForQuery();
                }, $this->values)
            ]
        ];
    }
}
