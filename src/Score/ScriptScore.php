<?php


namespace Novaway\ElasticsearchClient\Score;


use Novaway\ElasticsearchClient\Script\ScriptingLanguage;
use Novaway\ElasticsearchClient\Script\Traits\ScriptTrait;

class ScriptScore implements FunctionScore
{
    use ScriptTrait {
        formatForQuery as scriptFormatForQuery;
    }

    public function __construct(string $source, array $params = [], string $lang = ScriptingLanguage::PAINLESS)
    {
        if (!in_array($lang, ScriptingLanguage::toArray())) {
            throw new \Exception('$lang should be one of ' . implode(",", ScriptingLanguage::toArray()) . ". $lang given");
        }

        $this->lang = $lang;
        $this->source = $source;
        $this->params = $params;
    }

    public function formatForQuery(): array
    {
        return ['script_score' => $this->scriptFormatForQuery()];
    }


}
