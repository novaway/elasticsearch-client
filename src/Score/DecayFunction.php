<?php

namespace Novaway\ElasticsearchClient\Score;

use MyCLabs\Enum\Enum;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html#_supported_decay_functions
 */
class DecayFunction extends Enum
{
    const GAUSS = 'gauss';
    const EXP = 'exp';
    const LINEAR = 'linear';
}