<?php

namespace Novaway\ElasticsearchClient\FunctionScore;

abstract class DecayFunctionScore implements FunctionScoreInterface
{
    # See doc for parameter configuration
    # https://www.elastic.co/guide/en/elasticsearch/guide/1.x/decay-functions.html#img-decay-functions
    const DEFAULT_DECAY_FUNCTION = 'gauss';
    const DEFAULT_OFFSET = 0;
    const DEFAULT_SCALE = 0;
    const DEFAULT_DECAY = 0;

    /** @var array */
    protected $params;

    /**
     * @return mixed
     * @throws \UnexpectedValueException when origin can not be constructed
     */
    abstract protected function getOriginValue();

    /**
     * GeolocationFunctionScore constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery()
    {
        $params = $this->params;

        if (!isset($this->params['field'])) {
            return null;
        }

        try {
            $origin = $this->getOriginValue();
        } catch (\UnexpectedValueException $e) {
            return null;
        }

        $function = isset($params['decay_function']) ? $params['decay_function'] : static::DEFAULT_DECAY_FUNCTION;
        $offset = isset($params['offset']) ? $params['offset'] : static::DEFAULT_OFFSET;
        $scale = isset($params['scale']) ? $params['scale'] : static::DEFAULT_SCALE;
        $decay = isset($params['decay']) ? $params['decay'] : static::DEFAULT_DECAY;

        $paramOrigin = $origin === null ? [] : ['origin' => $origin];

        return [
            $function => [
                $params['field'] => array_merge(
                    $paramOrigin,
                    [
                        'offset' => $offset,
                        'scale'  => $scale,
                        'decay'  => $decay,
                    ]
                )
            ]
        ];
    }

}
