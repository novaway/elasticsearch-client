<?php


namespace Novaway\ElasticsearchClient\Score;

use Novaway\ElasticsearchClient\Query\Compound\FunctionScore as FunctionScore;
use Webmozart\Assert\Assert;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html#function-decay
 */
class DecayFunctionScore implements FunctionScore
{
    /** @deprecated use Novaway\ElasticsearchClient\Score\DecayFunction::GAUSS instead  */
    const GAUSS = 'gauss';
    /** @deprecated use Novaway\ElasticsearchClient\Score\DecayFunction::EXP instead  */
    const EXP = 'exp';
    /** @deprecated use Novaway\ElasticsearchClient\Score\DecayFunction::LINEAR instead  */
    const LINEAR = 'linear';

    /** @deprecated use Novaway\ElasticsearchClient\Score\DecayFunction::toArray() instead  */
    public static $availableFunctions = [
        self::GAUSS,
        self::EXP,
        self::LINEAR
    ];

    const DECAY = 0.5;

    /** @var string */
    private $property;
    /** @var string */
    private $function;
    private $origin;
    /** @var string */
    private $offset;
    /** @var string */
    private $scale;
    /** @var float */
    private $decay;
    /** @var array */
    private $options;

    public function __construct(string $property, string $function, $origin, string $offset, string $scale, array $options = [], float $decay = self::DECAY)
    {
        Assert::oneOf($function, DecayFunction::toArray());
        $this->property = $property;
        $this->function = $function;
        $this->origin = $origin;
        $this->offset = $offset;
        $this->scale = $scale;
        $this->decay = $decay;
        $this->options = $options;
    }

    public function formatForQuery(): array
    {
        return array_merge([$this->function => [
                $this->property => [
                    'origin' => $this->origin,
                    'offset' => $this->offset,
                    'scale' => $this->scale,
                    'decay' => $this->decay
                ]
            ]
        ], $this->options);
    }
}

