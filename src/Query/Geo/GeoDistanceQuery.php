<?php

namespace Novaway\ElasticsearchClient\Query\Geo;

use Novaway\ElasticsearchClient\Filter\Filter;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\Query;
use Webmozart\Assert\Assert;

class GeoDistanceQuery implements Query, Filter
{
    /** @var string */
    private $property;
    /** @var float */
    private $latitude;
    /** @var float */
    private $longitude;
    /** @var float */
    private $distance;
    /** @var string */
    private $combiningFactor;
    /** @var string */
    private $unit;
    /** @var array */
    private $options;

    /**
     * GeoDistanceFilter constructor.
     * @param string $property
     * @param float $latitude
     * @param float $longitude
     * @param float $distance
     * @param string $combiningFactor
     * @param string $unit Should be one of those https://www.elastic.co/guide/en/elasticsearch/reference/2.3/common-options.html#distance-units
     * @param array $options Used to pass options from https://www.elastic.co/guide/en/elasticsearch/reference/2.3/query-dsl-geo-distance-query.html#_options_4
     */
    public function __construct(string $property, float $latitude, float $longitude, float $distance, string $combiningFactor = CombiningFactor::FILTER, string $unit = DistanceUnits::KM, array $options = [])
    {
        Assert::oneOf($combiningFactor, CombiningFactor::toArray());
        Assert::oneOf($unit, DistanceUnits::toArray());

        $this->property  = $property;
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->distance  = $distance;
        $this->combiningFactor = $combiningFactor;
        $this->unit = $unit;
        $this->options = $options;
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
            'geo_distance' => array_merge([
                'distance'     => sprintf('%01.2f%s', $this->distance, $this->unit),
                $this->property => [
                    'lat' => $this->latitude,
                    'lon' => $this->longitude
                ]
            ], $this->options)
        ];
    }
}
