<?php


namespace Novaway\ElasticsearchClient\Query\FullText;


use Novaway\ElasticsearchClient\Query\BoostableField;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class MultiMatchQuery implements Query
{
    const BEST_FIELDS = 'best_fields';
    const MOST_FIELDS = 'most_fields';
    const CROSS_FIELDS = 'cross_fields';
    const PHRASE = 'phrase';
    const PHRASE_PREFIX = 'phrase_prefix';
    /** @var string */
    private $value;
    /** @var string[]|BoostableField[] */
    private $fields;
    /** @var array */
    private $options;
    /** @var string */
    private $combiningFactor;

    /**
     * @param string $value the query value to search for
     * @param array $fields the fields to search for. Should either contain strings or BoostableField
     * @param string $combiningFactor the combining factor
     * @param array $options additional options
     */
    public function __construct(string $value, array $fields, string $combiningFactor = CombiningFactor::SHOULD,array $options = [])
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        $fields = array_map(function($field) {
            if (!($field instanceof BoostableField || is_string($field))) {
                throw new \Exception('$fields array should either contain strings or BoostableField');
            };
            // if the field is a string, the cast doesn't do anything
            // if the field is a BoostableField, it willl be cast as string using the __toString method
            return (string)$field;
        }, $fields);

        $this->value = $value;
        $this->fields = $fields;
        $this->options = $options;
        $this->combiningFactor = $combiningFactor;
    }
    
    public function formatForQuery(): array
    {
        return [
            'multi_match' =>
                array_merge([
                    'query' => $this->value,
                    'fields' => $this->fields
                ], $this->options)
        ];
    }

    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }
}
