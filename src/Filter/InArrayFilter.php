<?php

namespace Novaway\ElasticsearchClient\Filter;

class InArrayFilter
{
    /** @var string */
    private $property;
    /** @var array */
    private $values;

    /**
     * InArrayFilter constructor.
     * @param string $property
     * @param array $values
     */
    public function __construct(string $property, array $values)
    {
        $this->property = $property;
        $this->values = $values;
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
                }, $values)
            ]
        ];
    }
}
