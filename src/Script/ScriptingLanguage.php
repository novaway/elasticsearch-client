<?php


namespace Novaway\ElasticsearchClient\Script;


use MyCLabs\Enum\Enum;

final class ScriptingLanguage extends Enum
{
    const PAINLESS = 'painless';
    const EXPRESSION = 'expression';
    const MUSTACHE = 'mustache';
}
