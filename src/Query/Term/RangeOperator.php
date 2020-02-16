<?php

namespace Novaway\ElasticsearchClient\Query\Term;

use MyCLabs\Enum\Enum;

class RangeOperator extends Enum
{
    const GREATER_THAN_OPERATOR = 'gt';
    const GREATER_THAN_OR_EQUAL_OPERATOR = 'gte';
    const LESS_THAN_OPERATOR = 'lt';
    const LESS_THAN_OR_EQUAL_OPERATOR = 'lte';
}