<?php


namespace Novaway\ElasticsearchClient\Query\Term;

use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class ExistsQuery implements Filter, Query
{
    /** @var string */
    protected $property;
    /** @var string */
    private $combiningFactor;

    public function __construct(string $property, $combiningFactor = CombiningFactor::FILTER)
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        $this->property = $property;
        $this->combiningFactor = $combiningFactor;
    }

    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery(): array
    {
        return ['exists' => [ 'field' => $this->property]];
    }

}
