<?php

namespace Novaway\ElasticsearchClient\Query;

class MatchQuery implements Query
{
    /** @var string */
    private $combiningFactor;

    /** @var string */
    private $field;

    /** @var mixed */
    private $value;

    /**
     * MatchQuery constructor.
     *
     * @param string $field
     * @param mixed  $value
     */
    public function __construct($field, $value, $combiningFactor = CombiningFactor::MUST)
    {
        $this->field = $field;
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
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function formatForQuery(): array
    {
        return [
                'match' => [
                    $this->getField() => $this->getValue()
                ]
            ];
    }


}
