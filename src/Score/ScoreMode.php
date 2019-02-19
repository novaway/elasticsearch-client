<?php


namespace Novaway\ElasticsearchClient\Score;


use MyCLabs\Enum\Enum;

class ScoreMode extends Enum
{
    const MULTIPLY = 'multiply';
    const FIRST = 'first';
    const SUM = 'sum';
    const AVG = 'avg';
    const MAX = 'max';
    const MIN = 'min';
}