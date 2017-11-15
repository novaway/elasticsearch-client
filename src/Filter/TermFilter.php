<?php

namespace Novaway\ElasticsearchClient\Filter;

class TermFilter implements Filter
{
    /** @var string */
    private $property;
    /** @var string */
    private $value;

    /**
     * TermFilter constructor.
     * @param string $property
     * @param string $value
     */
    public function __construct(string $property, string $value)
    {
        $this->property = $property;
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery(): array
    {
        return ['term' => [$this->property => $this->value]];
    }
}

