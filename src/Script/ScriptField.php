<?php


namespace Novaway\ElasticsearchClient\Script;


use Novaway\ElasticsearchClient\Script\Traits\ScriptTrait;
use Webmozart\Assert\Assert;

class ScriptField
{
    use ScriptTrait;

    /** @var string */
    protected $field;

    public function __construct(string $field, string $source, array $params = [], string $lang = ScriptingLanguage::PAINLESS)
    {
        Assert::oneOf($lang, ScriptingLanguage::toArray());

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
