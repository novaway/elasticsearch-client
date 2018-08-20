<?php

namespace Test\Unit\Novaway\ElasticsearchClient\Query;

use atoum\test;
use Novaway\ElasticsearchClient\Aggregation\Aggregation;
use Novaway\ElasticsearchClient\Filter\TermFilter;
use Novaway\ElasticsearchClient\Query\BoostMode;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\MatchQuery;
use Novaway\ElasticsearchClient\Score\FieldValueFactorScore;
use Novaway\ElasticsearchClient\Score\RandomScore;

class QueryBuilder extends test
{
    public function testCreateNew()
    {
        $this
            ->given($class = $this->testedClass->getClass())
            ->if($queryBuilder = $class::createNew(40, 20, 0.1))
            ->then
            ->array($queryBuilder->getQueryBody())
            ->integer['from']->isEqualTo(40)
            ->integer['size']->isEqualTo(20)
            ->float['min_score']->isEqualTo(0.1)
        ;
    }

    public function testDefaultValues()
    {
        $this
            ->given($this->newTestedInstance())
            ->then
            ->array($this->testedInstance->getQueryBody())
            ->integer['from']->isEqualTo(0)
            ->integer['size']->isEqualTo(10)
            ->float['min_score']->isEqualTo(0.01)
        ;
    }

    public function testEditQueryParameters()
    {
        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->setOffset(15),
                $this->testedInstance->setLimit(5)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
            ->integer['from']->isEqualTo(15)
            ->integer['size']->isEqualTo(5)
        ;
    }

    public function testAddMatchQueries()
    {
        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->match('civility', 'm', CombiningFactor::MUST),
                $this->testedInstance->match('firstname', 'cedric', CombiningFactor::MUST),
                $this->testedInstance->match('nickname', 'skwi', CombiningFactor::SHOULD)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['must']->notHasKey('match_all')
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['civility' => ['query' => 'm', 'operator' => 'AND']]])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[1]->isEqualTo(['match' => ['firstname' => ['query' => 'cedric', 'operator' => 'AND']]])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::SHOULD]->array[0]->isEqualTo(['match' => ['nickname' => ['query' => 'skwi', 'operator' => 'AND']]])
        ;
    }

    public function testAddFilter()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter(new TermFilter('size', 'M')),
                $this->testedInstance->addFilter(new TermFilter('color', 'blue'))
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array['match_all']->isEqualTo([])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['filter']->array[0]->isEqualTo(['term' => ['size' => 'M']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['filter']->array[1]->isEqualTo(['term' => ['color' => 'blue']])
        ;
    }

    public function setMultipleFiltersAtOnce()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->setFilters([new TermFilter('size', 'M'), new TermFilter('color', 'blue')])
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
            ->array['query']->array['bool']->array['filter']->array[0]->isEqualTo(['term' => ['size' => 'M']])
            ->array($this->testedInstance->getQueryBody())
            ->array['query']->array['bool']->array['filter']->array[1]->isEqualTo(['term' => ['color' => 'blue']])
        ;
    }

    public function testAddCombination()
    {
        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter(new TermFilter('size', 'M')),
                $this->testedInstance->match('firstname', 'cedric', CombiningFactor::MUST),
                $this->testedInstance->match('nickname', 'skwi', CombiningFactor::SHOULD)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->notHasKey('match_all')
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['filter']->array[0]->isEqualTo(['term' => ['size' => 'M']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['firstname' => ['query' => 'cedric', 'operator' => 'AND']]])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::SHOULD]->array[0]->isEqualTo(['match' => ['nickname' => ['query' => 'skwi', 'operator' => 'AND']]])
        ;
    }

    public function testAddAggregation()
    {
          $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addAggregation(new Aggregation('avg_likes', 'avg', 'likes')),
                $this->testedInstance->addAggregation(new Aggregation('users', 'terms', 'user')),
                $this->testedInstance->addAggregation(new Aggregation('date_range', 'date_range', 'date', ['format' => 'MM-yyy']))
            )
            ->then
            ->array($this->testedInstance->getQueryBody()['aggregations'])
            ->isEqualTo([
                'avg_likes' => [
                    'avg' => [
                        'field' => 'likes'
                        ],
                    ],
                'users' => [
                    'terms' => [
                        'field' => 'user'
                    ]
                ],
                'date_range' => [
                    'date_range' => [
                        'field' => 'date',
                        'format' => 'MM-yyy'
                    ]
                ],
            ])
        ;

    }


    public function testAddQuery()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addQuery(new MatchQuery('firstname', 'cedric', CombiningFactor::MUST))
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
            ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['firstname' => ['query' => 'cedric', 'operator' => 'AND']]])
        ;
    }

    public function testAddRandomScore()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addQuery(new MatchQuery('firstname', 'cedric', CombiningFactor::MUST)),
                $this->testedInstance->addFunctionScore(new RandomScore('testSeed'))
            )
            ->then
            ->array($this->testedInstance->getQueryBody()['query'])
            ->hasKey('function_score')
            ->array($this->testedInstance->getQueryBody()['query']['function_score'])
            ->hasKey('functions')
            ->array($this->testedInstance->getQueryBody()['query']['function_score']['functions'][0])
            ->isEqualTo([
                'random_score' => ['seed' => 'testSeed']
            ]);
        ;
    }


    public function testAddFiedValueFactorScore()
    {
        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addQuery(new MatchQuery('firstname', 'cedric', CombiningFactor::MUST)),
                $this->testedInstance->addFunctionScore(new FieldValueFactorScore('age', FieldValueFactorScore::SQUARE, 2,15))
            )
            ->then
            ->array($this->testedInstance->getQueryBody()['query'])
            ->hasKey('function_score')
            ->array($this->testedInstance->getQueryBody()['query']['function_score'])
            ->hasKey('functions')
            ->array($this->testedInstance->getQueryBody()['query']['function_score']['functions'][0])
            ->isEqualTo([
                'field_value_factor' => [
                    'field' => 'age',
                    'modifier' => 'square',
                    'factor' => 2,
                    'missing' => 15
                ]
            ]);
        ;
    }

    public function testSetPostFilter()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->setPostFilter(new TermFilter('size', 'M'))
            )
            ->then
            ->array($this->testedInstance->getQueryBody()['post_filter'])
            ->isEqualTo(['term' => ['size' => 'M']])
        ;
    }

    public function testSetBoostMode()
    {

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addQuery(new MatchQuery('firstname', 'cedric', CombiningFactor::MUST)),
                $this->testedInstance->addFunctionScore(new FieldValueFactorScore('age', FieldValueFactorScore::SQUARE, 2, 15)),
                $this->testedInstance->setBoostMode(BoostMode::MAX)
            )
            ->then
            ->array($this->testedInstance->getQueryBody()['query'])
            ->hasKey('function_score')
            ->array($this->testedInstance->getQueryBody()['query']['function_score'])
            ->hasKey('boost_mode')
            ->string($this->testedInstance->getQueryBody()['query']['function_score']['boost_mode'])
            ->isEqualTo( 'max');
    }
}
