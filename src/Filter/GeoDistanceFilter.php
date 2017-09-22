<?php

namespace Novaway\ElasticsearchClient\Filter;

class GeoDistanceFilter implements Filter
{
    /** @var string */
    private $property;
    /** @var float */
    private $latitude;
    /** @var float */
    private $longitude;
    /** @var int */
    private $distance;

    /**
     * GeoDistanceFilter constructor.
     * @param string $property
     * @param float  $latitude
     * @param float  $longitude
     * @param int    $distance
     */
    public function __construct(string $property, float $latitude, float $longitude, int $distance)
    {
        $this->property  = $property;
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->distance  = $distance;
    }

    /**
     * @inheritdoc
     */
    public function formatForQuery(): array
    {
        return [
            'geo_distance' => [
                'distance'     => sprintf('%dkm', $this->distance),
                $this->property => [
                    'lat' => $this->latitude,
                    'lon' => $this->longitude
                ]
            ]
        ];
    }

}