<?php


namespace Novaway\ElasticsearchClient\Query\Geo;

use MyCLabs\Enum\Enum;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-query.html#_spatial_relations
 */
class SpatialRelation extends Enum
{
    const INTERSECTS = 'intersects';
    const DISJOINT = 'disjoint';
    const WITHIN = 'within';
    const CONTAINS = 'contains';
}