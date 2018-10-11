<?php


namespace Novaway\ElasticsearchClient\Script;


final class ScriptingLanguage
{
    const PAINLESS = 'painless';
    const EXPRESSION = 'expression';
    const MUSTACHE = 'mustache';

    public static $available = [
        self::PAINLESS,
        self::EXPRESSION,
        self::MUSTACHE
    ];
}
