<?php

namespace Novaway\ElasticsearchClient\FunctionScore;

class FunctionScoreFactory
{
    /** @var array */
    private $functionScoreList;

    /**
     * FunctionScoreFactory constructor.
     *
     * @param array $functionScoreList
     */
    public function __construct(array $functionScoreList = [])
    {
        $this->functionScoreList = $functionScoreList;
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
        if (!isset($this->functionScoreList[$key])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No function score found for key "%s". Available keys are %s',
                    $key,
                    implode(', ',
                        array_map(
                            function ($key) {
                                return sprintf('"%s"', $key);
                            },
                            array_keys($this->functionScoreList)
                        )
                    )
                )
            );
        }

        $functionScore = new $this->functionScoreList[$key]($params);
        if (!$functionScore instanceof FunctionScoreInterface) {
            throw new \UnexpectedValueException(sprintf('The function score for key "%s" does not implement FunctionScoreInterface', $key));
        }

        return $functionScore;
    }
}
