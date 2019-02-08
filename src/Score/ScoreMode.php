<?php


namespace Novaway\ElasticsearchClient\Score;


class ScoreMode
{
    const MULTIPLY = 'multiply';
    const FIRST = 'first';
    const SUM = 'sum';
    const AVG = 'avg';
    const MAX = 'max';
    const MIN = 'min';


    public static $available = [
        self::MULTIPLY,
        self::FIRST,
        self::SUM,
        self::AVG,
        self::MAX,
        self::MIN
    ];
}