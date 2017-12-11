<?php

namespace Test\Unit\Novaway\ElasticsearchClient\Filter;

use atoum\test;
use Novaway\ElasticsearchClient\Query\CombiningFactor;

class GeoDistanceFilter extends test
{
    public function testFormat()
    {
        $this
            ->given($this->newTestedInstance('location', '4.5', '45', '200', CombiningFactor::FILTER, 'm', ['distance_type' => 'arc']))
            ->then
            ->array($this->testedInstance->formatForQuery())
            ->isEqualTo([
                'geo_distance' => [
                    'distance'     => '200m',
                    'location' => [
                        'lat' => '4.5',
                        'lon' => '45'
                    ],
                    'distance_type' => 'arc'
                ]
            ])
        ;
    }
}