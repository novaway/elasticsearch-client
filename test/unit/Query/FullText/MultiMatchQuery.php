<?php


namespace Test\Unit\Novaway\ElasticsearchClient\Query\FullText;


use atoum\test;
use Novaway\ElasticsearchClient\Query\BoostableField;
use Novaway\ElasticsearchClient\Query\CombiningFactor;

class MultiMatchQuery extends test
{
    public function testAddQuery()
    {
        $this
            ->given($this->newTestedInstance('terry patchett', [
                new BoostableField('author'),
                new BoostableField('benevolentGod', 10),
                'ourangoutanologist'
            ], CombiningFactor::SHOULD,  [
                'type' => 'cross_fields'
            ]))
            ->then
            ->array($this->testedInstance->formatForQuery())
            ->isEqualTo([
                'multi_match' => [
                    'query' => 'terry patchett',
                    'fields' => ['author', 'benevolentGod^10', 'ourangoutanologist'],
                    'type' => 'cross_fields'
                ]
            ])
        ;
    }
}
