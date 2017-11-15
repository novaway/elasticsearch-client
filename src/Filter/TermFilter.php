<?php

namespace Novaway\ElasticsearchClient\Filter;

use Novaway\ElasticsearchClient\Query\CombiningFactor;

class TermFilter implements Filter
{
    /** @var string */
    private $property;
    /** @var string */
    private $value;
    /** @var string */
    private $combiningFactor;

    /**
     * TermFilter constructor.
     * @param string $property
     * @param string $value
     */
    public function __construct(string $property, string $value, string $combiningFactor = CombiningFactor::FILTER)
    {
        $this->property = $property;
        $this->value = $value;
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
        return ['term' => [$this->property => $this->value]];
    }
}

