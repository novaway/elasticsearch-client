<?php


namespace Novaway\ElasticsearchClient\Score;


use MyCLabs\Enum\Enum;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html#query-dsl-function-score-query
 */
class ScoreMode extends Enum
{
    const MULTIPLY = 'multiply';
    const FIRST = 'first';
    const SUM = 'sum';
    const AVG = 'avg';
    const MAX = 'max';
    const MIN = 'min';
}