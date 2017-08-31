<?php


namespace Novaway\ElasticsearchClient\Aggregation;


class Aggregation
{
    /** @var  string */
    protected $category;
    /** @var  string */
    protected $name;
    /** @var  string */
    protected $field;
    /** @var  array */
    protected $options;

    /**
     * Aggregation constructor.
     * @param string    $name         name of the key in results
     * @param string    $category     category of aggregation : terms, max, avg ...
     * @param string    $field        field on which the aggregation is done
     * @param array     $options      extraneous parameters given to the aggregation, needed in some categories
     */
    public function __construct(string $name, string $category, string $field, array $options = [])
    {
        $this->category = $category;
        $this->name = $name;
        $this->field = $field;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getParameters(): array
    {
        return array_merge(['field' => $this->getField()], $this->getOptions());
    }

}