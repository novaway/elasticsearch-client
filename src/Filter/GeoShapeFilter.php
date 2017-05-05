<?php

namespace Novaway\ElasticsearchClient\Filter;

class GeoShapeFilter implements FilterInterface
{
    # See doc for parameter configuration
    # https://www.elastic.co/guide/en/elasticsearch/reference/2.3/geo-shape.html#_sorting_and_retrieving_index_shapes
    const DEFAULT_RADIUS = '100m';
    const DEFAULT_RELATION = 'INTERSECTS';

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

        if (!isset($params['field'], $params['coordinates'])) {
            return null;
        }

        $radius   = isset($params['radius']) ? $params['radius'] : self::DEFAULT_RADIUS;
        $relation = isset($params['relation']) ? $params['relation'] : self::DEFAULT_RELATION;

        return [
            'geo_shape' => [
                $params['field'] => [
                    'shape'    => [
                        'type'        => 'circle',
                        'coordinates' => [$params['coordinates']['lat'], $params['coordinates']['long']],
                        'radius'      => $radius
                    ],
                    'relation' => $relation
                ]
            ]
        ];

    }

}
