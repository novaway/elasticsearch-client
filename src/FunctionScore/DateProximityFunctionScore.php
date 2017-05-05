<?php

namespace Novaway\ElasticsearchClient\FunctionScore;

class DateProximityFunctionScore extends DecayFunctionScore
{
    # See doc for parameter configuration
    # https://www.elastic.co/guide/en/elasticsearch/guide/1.x/decay-functions.html#img-decay-functions
    const DEFAULT_DECAY_FUNCTION = 'linear';
    const DEFAULT_OFFSET = '1w';
    const DEFAULT_SCALE = '5w';
    const DEFAULT_DECAY = 0.1;

    /**
     * @inheritDoc
     */
    public function getOriginValue()
    {
        if (isset($this->params['date'])) {
            return $this->params['date'];
        }

        return null;
    }

}
