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
        if (!in_array($lang, ScriptingLanguage::$available)) {
            throw new \Exception('$lang should be one of ' . implode(",", ScriptingLanguage::$available) . ". $lang given");
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
