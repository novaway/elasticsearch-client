<?php


namespace Novaway\ElasticsearchClient\Query\Geo;


use MyCLabs\Enum\Enum;

class DistanceUnits extends Enum
{
    const MILES = 'miles';
    const MI = 'mi';
    const YARDS = 'yards';
    const YD = 'yd';
    const FEET = 'feet';
    const FT = 'ft';
    const INCH = 'inch';
    const IN = 'in';
    const KILOMETERS = 'kilometers';
    const KM = 'km';
    const METERS = 'meters';
    const M = 'm';
    const CENTIMETERS = 'centimeters';
    const CM = 'cm';
    const MILLIMETERS = 'millimeters';
    const MM = 'mm';
    const NAUTICALMILES = 'nauticalmiles';
    const NMI = 'nmi';
    const NM = 'nm';
}