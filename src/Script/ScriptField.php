<?php


namespace Novaway\ElasticsearchClient\Script;


use Novaway\ElasticsearchClient\Script\Traits\ScriptTrait;

class ScriptField
{
    use ScriptTrait;

    /** @var string */
    protected $field;

    public function __construct(string $field, string $source, array $params = [], string $lang = ScriptingLanguage::PAINLESS)
    {
        if (!in_array($lang, ScriptingLanguage::toArray())) {
            throw new \Exception('$lang should be one of ' . implode(",", ScriptingLanguage::toArray()) . ". $lang given");
        }

        $this->field = $field;
        $this->lang = $lang;
        $this->source = $source;
        $this->params = $params;
    }

    public function getField():string
    {
        return $this->field;
    }
}
