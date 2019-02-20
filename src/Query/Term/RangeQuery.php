<?php

namespace Novaway\ElasticsearchClient\Query\Term;

use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class RangeQuery implements Filter, Query
{
    const GREATER_THAN_OPERATOR = 'gt';
    const GREATER_THAN_OR_EQUAL_OPERATOR = 'gte';
    const LESS_THAN_OPERATOR = 'lt';
    const LESS_THAN_OR_EQUAL_OPERATOR = 'lte';

    /** @var string */
    private $property;
    /** @var mixed */
    private $value;
    /** @var array */
    private $operator;
    /** @var string */
    private $combiningFactor;

    public function __construct(string $property, $value, $operator, string $combiningFactor = CombiningFactor::FILTER)
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());

        if(is_array($value) && !is_array($operator)) {
            throw new \InvalidArgumentException("Operator should be an array when range filter value is an array");
        }
        if(!is_array($value) && is_array($operator)) {
            throw new \InvalidArgumentException("Operator can't be an array if range filter is not an array");
        }
        if(is_array($value) && is_array($operator) && count($value) !== count($operator)) {
            throw new \InvalidArgumentException("Number of provided operator does not match number of provided values");
        }

        $this->property = $property;
        $this->value = is_array($value) ? $value : [$value];
        $this->operator = is_array($operator) ? $operator : [$operator];
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
        $rangeConditions = [];

        $valueCount = count($this->value);
        for ($i = 0; $i < $valueCount; $i++){
            $rangeConditions[] = [$this->operator[$i] => $this->value[$i]];
        }

        return [
            'range' => [
                $this->property => $rangeConditions
            ]
        ];
    }
}
