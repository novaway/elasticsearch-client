<?php


namespace Novaway\ElasticsearchClient\Score;


class DecayFunctionScore implements FunctionScore
{
    const GAUSS = 'gauss';
    const EXP = 'exp';
    const LINEAR = 'linear';

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
        if (!in_array($function, self::$availableFunctions)) {
            throw new \InvalidArgumentException(sprintf("function should be one of %s : %s given", implode("," , self::$availableFunctions), $function));
        }
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

