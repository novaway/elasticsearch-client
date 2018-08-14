<?php

namespace Novaway\ElasticsearchClient\Score;


class FieldValueFactorScore implements FunctionScore
{

    const NONE = "none";
    const LOG = "log";
    const LOG1P = "log1p";
    const LOG2P = "log2p";
    const LN = "ln";
    const LN1P = "ln1p";
    const LN2P = "ln2p";
    const SQUARE = "square";
    const SQRT = "sqrt";
    const RECIPROCAL = "reciprocal";

    /** @var string Field to be extracted from the document. */
    private $field;

    /** @var string Modifier to apply to the field value, can be one of:
     * none, log, log1p, log2p, ln, ln1p, ln2p, square, sqrt, or reciprocal.
     * Defaults to none.
     */
    private $modifier = self::NONE;

    /** @var float Optional factor to multiply the field value with, defaults to 1. */
    private $factor = 1;

    /** @var float Value used if the document doesn’t have that field.
     * The modifier and factor are still applied to it as though it were read from the document.
     */
    private $missing;

    /** @var array */
    private $options = [];

    /**
     * FieldValueFactorScore constructor.
     * @param string $field
     * @param string $modifier
     * @param float $factor
     * @param float $missing
     * @param array $options
     */
    public function __construct(string $field, string $modifier, float $factor, float $missing = null, array $options = array())
    {

        if (!in_array($modifier, [self::NONE, self::LN, self::LN1P, self::LN2P, self::LOG, self::LOG1P, self::LOG2P, self::SQUARE, self::SQRT, self::RECIPROCAL])) {
            throw new \InvalidArgumentException(sprintf("function should be one of %s, %s, %s, %s, %s, %s, %s, %s, %s, %s : %s given", self::NONE, self::LN, self::LN1P, self::LN2P, self::LOG, self::LOG1P, self::LOG2P, self::SQUARE, self::SQRT, self::RECIPROCAL, $modifier));
        }
        $this->field = $field;
        $this->modifier = $modifier;
        $this->factor = $factor;
        $this->missing = $missing;
        $this->options = $options;
    }


    public function formatForQuery(): array
    {
        $params = [
            'field' => $this->field,
            'modifier' => $this->modifier,
            'factor' => $this->factor
        ];
        if ($this->missing) {
            $params['missing'] = $this->missing;
        }
        return array_merge(["field_value_factor" => $params], $this->options);
    }

}