<?php


namespace Test\Unit\Novaway\ElasticsearchClient\Query;


use atoum\test;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\MatchQuery;

class BoolQuery extends test
{
    public function testAddQuery()
    {
        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addClause(new MatchQuery('firstname', 'bruce', CombiningFactor::MUST)),
                $this->testedInstance->addClause(new MatchQuery('gender', 'male', CombiningFactor::MUST)),
                $this->testedInstance->addClause(new MatchQuery('lastname', 'wayne', CombiningFactor::MUST_NOT))
            )
            ->then
            ->array($this->testedInstance->formatForQuery())
            ->isEqualTo([
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'firstname' => 'bruce'
                            ],
                        ],[
                            'match' => [
                                'gender' => 'male'
                            ],
                        ]
                    ],
                    'must_not' => [
                        [
                            'match' => [
                                'lastname' => 'wayne'
                            ]
                        ]
                    ],
                ]
            ])
        ;
    }
}
