<?php

namespace Novaway\ElasticsearchClient\Query;

use MyCLabs\Enum\Enum;

final class CombiningFactor extends Enum
{
    const MUST = 'must';
    const SHOULD = 'should';
    const MUST_NOT = 'must_not';
    const FILTER = 'filter';
}
