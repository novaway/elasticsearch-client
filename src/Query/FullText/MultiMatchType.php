<?php

namespace Novaway\ElasticsearchClient\Query\FullText;

use MyCLabs\Enum\Enum;

class MultiMatchType extends Enum
{
    const BEST_FIELDS = 'best_fields';
    const MOST_FIELDS = 'most_fields';
    const CROSS_FIELDS = 'cross_fields';
    const PHRASE = 'phrase';
    const PHRASE_PREFIX = 'phrase_prefix';
}