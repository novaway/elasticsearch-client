<?php


namespace Novaway\ElasticsearchClient\Script;


class ScriptField
{
    /** @var string */
    protected $field;
    /** @var string */
    protected $lang;
    /** @var string */
    protected $source;
    /** @var array */
    protected $params;

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

    public function formatForQuery(): array
    {

        $script =  [
            'lang' => $this->lang,
            'source' => $this->source
        ];

        if (!empty($this->params)) {
            $script['params'] = $this->params;
        }

        return ['script' => $script];
    }
}
