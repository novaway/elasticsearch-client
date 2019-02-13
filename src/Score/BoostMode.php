<?php


namespace Novaway\ElasticsearchClient\Score;


use MyCLabs\Enum\Enum;

class BoostMode extends Enum
{
    const MULTIPLY = 'multiply';
    const REPLACE = 'replace';
    const SUM = 'sum';
    const AVG = 'avg';
    const MAX = 'max';
    const MIN = 'min';
}