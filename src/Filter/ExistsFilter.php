<?php


namespace Novaway\ElasticsearchClient\Filter;


use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Webmozart\Assert\Assert;

class ExistsFilter implements Filter
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
