<?php

namespace Novaway\ElasticsearchClient\Filter;

class ArrayContainsFilter implements FilterInterface
{
    const DEFAULT_MINIMUM_SHOULD_MATCH = 1;

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

        return [
            'terms' => [
                $params['field'] => $params['values'],
            ]
        ];
    }

}
