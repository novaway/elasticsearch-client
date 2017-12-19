<?php


namespace Novaway\ElasticsearchClient\Filter;


use Novaway\ElasticsearchClient\Query\CombiningFactor;

class ExistsFilter implements Filter
{
    /** @var string */
    protected $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getCombiningFactor(): string
    {
        return CombiningFactor::FILTER;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery(): array
    {
        return ['exists' => [ 'field' => $this->property]];
    }

}
