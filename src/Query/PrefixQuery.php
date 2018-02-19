<?php


namespace Novaway\ElasticsearchClient\Query;


class PrefixQuery implements Query
{
    /** @var string */
    private $combiningFactor;
    /** @var string */
    private $field;
    /** @var mixed */
    private $value;
    /** @var float */
    private $boost;

    public function __construct($field, $value, $combiningFactor = CombiningFactor::MUST, float $boost = 1)
    {
        $this->field = $field;
        $this->value = $value;
        $this->combiningFactor = $combiningFactor;
        $this->boost = $boost;
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
            'prefix' => [
                $this->getField() =>  [
                    'prefix' => $this->getValue(),
                    'boost' => $this->boost
                ]
            ]
        ];
    }
}
