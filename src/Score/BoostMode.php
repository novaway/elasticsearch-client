<?php


namespace Novaway\ElasticsearchClient\Score;


class BoostMode
{
    const MULTIPLY = 'multiply';
    const REPLACE = 'replace';
    const SUM = 'sum';
    const AVG = 'avg';
    const MAX = 'max';
    const MIN = 'min';

    public static $available = [
        self::MULTIPLY,
        self::REPLACE,
        self::SUM,
        self::AVG,
        self::MAX,
        self::MIN
    ];
}