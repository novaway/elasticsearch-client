<?php

namespace Novaway\ElasticsearchClient\FunctionScore;

class GeolocationFunctionScore extends DecayFunctionScore
{
    # See doc for parameter configuration
    # https://www.elastic.co/guide/en/elasticsearch/guide/1.x/decay-functions.html#img-decay-functions
    const DEFAULT_DECAY_FUNCTION = 'gauss';
    const DEFAULT_OFFSET = 0;
    const DEFAULT_SCALE = '1km';
    const DEFAULT_DECAY = 0;

    /**
     * @inheritDoc
     */
    public function getOriginValue()
    {
        if (!isset($this->params['coordinates'], $this->params['coordinates']['lat'], $this->params['coordinates']['long'])) {
            throw new \UnexpectedValueException('Missing coordinates in search parameters');
        }

        return sprintf('%s,%s', $this->params['coordinates']['lat'], $this->params['coordinates']['long']);
    }

}
