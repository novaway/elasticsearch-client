<?php


namespace Novaway\ElasticsearchClient\Score;


use Novaway\ElasticsearchClient\Script\ScriptingLanguage;
use Novaway\ElasticsearchClient\Script\Traits\ScriptTrait;
use Webmozart\Assert\Assert;
use Novaway\ElasticsearchClient\Query\Compound\FunctionScore as FunctionScore;

class ScriptScore implements FunctionScore
{
    use ScriptTrait {
        formatForQuery as scriptFormatForQuery;
    }

    public function __construct(string $source, array $params = [], string $lang = ScriptingLanguage::PAINLESS)
    {
        Assert::oneOf($lang, ScriptingLanguage::toArray());
        $this->lang = $lang;
        $this->source = $source;
        $this->params = $params;
    }

    public function formatForQuery(): array
    {
        return ['script_score' => $this->scriptFormatForQuery()];
    }


}
