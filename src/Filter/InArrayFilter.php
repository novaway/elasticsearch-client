<?php

namespace Novaway\ElasticsearchClient\Filter;

class InArrayFilter implements FilterInterface
{
    /** @var array */
    private $params;

    /**
     * @inheritDoc
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @inheritDoc
     */
    public function formatForQuery()
    {
        $params = $this->params;

        if (!isset($params['field'], $params['values'])) {
            return null;
        }

        if (!count($params['values'])) {
            return null;
        }

        $field = $params['field'];

        return [
            'bool' => [
                'should' => array_map(function ($value) use ($field) {
                    return ['term' => [$field => $value]];
                }, $params['values'])
            ]
        ];
    }

}
