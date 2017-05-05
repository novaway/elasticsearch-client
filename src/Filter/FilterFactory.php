<?php

namespace Novaway\ElasticsearchClient\Filter;

class FilterFactory
{
    /** @var array */
    private $filterList;

    /**
     * FunctionScoreFactory constructor.
     *
     * @param array $filterList
     */
    public function __construct(array $filterList = [])
    {
        $this->filterList = $filterList;
    }

    /**
     * @param string $key
     * @param array  $params
     *
     * @return FunctionScoreInterface
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function create($key, array $params = [])
    {
        if (!isset($this->filterList[$key])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No filter found for key "%s". Available keys are %s',
                    $key,
                    implode(', ',
                        array_map(
                            function ($key) {
                                return sprintf('"%s"', $key);
                            },
                            array_keys($this->filterList)
                        )
                    )
                )
            );
        }

        $filter = new $this->filterList[$key]($params);
        if (!$filter instanceof FilterInterface) {
            throw new \UnexpectedValueException(sprintf('The filter for key "%s" does not implement FilterInterface', $key));
        }

        return $filter;
    }
}
