<?php

namespace Novaway\ElasticsearchClient\Query;

final class CombiningFactor
{
    const MUST = 'must';
    const SHOULD = 'should';
    const MUST_NOT = 'must_not';
    const FILTER = 'filter';
}
