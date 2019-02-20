<?php


namespace Test\Unit\Novaway\ElasticsearchClient\Query\Compound;


use atoum\test;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\FullText\MatchQuery;

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
