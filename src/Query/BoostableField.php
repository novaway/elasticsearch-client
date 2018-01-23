<?php


namespace Novaway\ElasticsearchClient\Query;


class BoostableField
{
    /** @var string */
    private $field;
    /** @var float */
    private $boost;

    public function __construct(string $field, float $boost = 1)
    {

        $this->field = $field;
        $this->boost = $boost;
    }

    public function __toString(): string
    {
        return $this->boost == 1 ? $this->field : sprintf('%s^%s', $this->field, $this->boost);
    }
}
