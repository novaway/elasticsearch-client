<?php


namespace Novaway\ElasticsearchClient\Script\Traits;


trait ScriptTrait
{
    /** @var string */
    protected $lang;
    /** @var string */
    protected $source;
    /** @var array */
    protected $params;

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
