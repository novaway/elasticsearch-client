<?php


namespace Novaway\ElasticsearchClient\Query\Geo;


use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-query.html#_inline_shape_definition
 */
class InlineGeoShapeQuery implements Query
{
    /** @var string */
    private $property;
    /** @var mixed */
    private $geoshape;
    /** @var string */
    private $combiningFactor;
    /** @var array */
    private $options;
    /** @var string */
    private $relation;

    public function __construct(
        string $property,
        $geoshape,
        string $combiningFactor = CombiningFactor::FILTER,
        string $relation = SpatialRelation::INTERSECTS,
        array $options = []
    ) {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        Assert::oneOf($relation, SpatialRelation::toArray());
        $this->property = $property;
        $this->geoshape = $geoshape;
        $this->combiningFactor = $combiningFactor;
        $this->options = $options;
        $this->relation = $relation;
    }

    /**
     * @return string
     */
    public function getCombiningFactor(): string
    {
        return $this->combiningFactor;
    }

    /**
     * @inheritdoc
     */
    public function formatForQuery(): array
    {
        return [
            'geo_shape' => array_merge([
                $this->property => [
                    'shape' => $this->geoshape,
                    'relation' => $this->relation
                ]
            ], $this->options)
        ];
    }
}
