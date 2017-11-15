<?php


namespace Test\Unit\Novaway\ElasticsearchClient\Query;


use atoum\test;
use Novaway\ElasticsearchClient\Query\CombiningFactor;

class BoolQuery extends test
{
    public function testAddQuery()
    {
        $mockFirstNameQuery = new \mock\Novaway\ElasticsearchClient\Query\MatchQuery('firstname', 'bruce', CombiningFactor::MUST);
        $mockGenderQuery = new \mock\Novaway\ElasticsearchClient\Query\MatchQuery('gender', 'male', CombiningFactor::MUST);
        $mockLastNameQuery = new \mock\Novaway\ElasticsearchClient\Query\MatchQuery('lastname', 'wayne', CombiningFactor::MUST_NOT);

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addClause($mockFirstNameQuery),
                $this->testedInstance->addClause($mockGenderQuery),
                $this->testedInstance->addClause($mockLastNameQuery)
            )
            ->then
            ->array($this->testedInstance->formatForQuery())
            ->isEqualTo([
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'firstname' => ['query' =>'bruce', 'operator' => 'AND']
                            ],
                        ],[
                            'match' => [
                                'gender' => ['query' => 'male', 'operator' => 'AND']
                            ],
                        ]
                    ],
                    'must_not' => [
                        [
                            'match' => [
                                'lastname' => ['query' => 'wayne', 'operator' => 'AND']
                            ]
                        ]
                    ],
                ]
            ])
        ;
    }
}
