<?php

namespace Test\Unit\Novaway\ElasticsearchClient\Query;

use atoum\test;
use Novaway\ElasticsearchClient\Query\BoolQuery;
use Novaway\ElasticsearchClient\Query\CombiningFactor;
use Novaway\ElasticsearchClient\Query\MatchQuery;

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
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['civility' => 'm']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[1]->isEqualTo(['match' => ['firstname' => 'cedric']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::SHOULD]->array[0]->isEqualTo(['match' => ['nickname' => 'skwi']])
        ;
    }

    public function testAddFilter()
    {
        $mockFilterSize = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterSize->getMockController()->formatForQuery = ['term' => ['size' => 'M']];
        $mockFilterSize->getMockController()->getCombiningFactor = CombiningFactor::FILTER;

        $mockFilterColor = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterColor->getMockController()->formatForQuery = ['term' => ['color' => 'blue']];
        $mockFilterColor->getMockController()->getCombiningFactor = CombiningFactor::FILTER;

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter($mockFilterSize),
                $this->testedInstance->addFilter($mockFilterColor)
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
        $mockFilterSize = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterSize->getMockController()->formatForQuery = ['term' => ['size' => 'M']];
        $mockFilterSize->getMockController()->getCombiningFactor = CombiningFactor::FILTER;

        $mockFilterColor = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterColor->getMockController()->formatForQuery = ['term' => ['color' => 'blue']];
        $mockFilterColor->getMockController()->getCombiningFactor = CombiningFactor::FILTER;

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->setFilters([$mockFilterSize, $mockFilterColor])
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
        $mockFilterSize = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterSize->getMockController()->formatForQuery = ['term' => ['size' => 'M']];
        $mockFilterSize->getMockController()->getCombiningFactor = CombiningFactor::FILTER;

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter($mockFilterSize),
                $this->testedInstance->match('firstname', 'cedric', CombiningFactor::MUST),
                $this->testedInstance->match('nickname', 'skwi', CombiningFactor::SHOULD)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->notHasKey('match_all')
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['filter']->array[0]->isEqualTo(['term' => ['size' => 'M']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['firstname' => 'cedric']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::SHOULD]->array[0]->isEqualTo(['match' => ['nickname' => 'skwi']])
        ;
    }

    public function testAddAggregation()
    {
        $mockAvgAggregation = new \mock\Novaway\ElasticsearchClient\Aggregation\Aggregation('avg_likes', 'avg', 'likes');
        $mockAvgAggregation->getMockController()->getParameters = ['field' => 'likes'];
        $mockAvgAggregation->getMockController()->getName = 'avg_likes';
        $mockAvgAggregation->getMockController()->getCategory = 'avg';

        $mockTermsAggregation = new \mock\Novaway\ElasticsearchClient\Aggregation\Aggregation('users', 'terms', 'user');
        $mockTermsAggregation->getMockController()->getParameters = ['field' => 'user'];
        $mockTermsAggregation->getMockController()->getName = 'users';
        $mockTermsAggregation->getMockController()->getCategory = 'terms';

        $mockRangeAggregation = new \mock\Novaway\ElasticsearchClient\Aggregation\Aggregation('date_range', 'date_range', 'date', ['format' => 'MM-yyy']);
        $mockRangeAggregation->getMockController()->getParameters = ['field' => 'date', 'format' => 'MM-yyy'];
        $mockRangeAggregation->getMockController()->getName = 'date_range';
        $mockRangeAggregation->getMockController()->getCategory = 'date_range';

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addAggregation($mockAvgAggregation),
                $this->testedInstance->addAggregation($mockTermsAggregation),
                $this->testedInstance->addAggregation($mockRangeAggregation)
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
        $mockQuery = new \mock\Novaway\ElasticsearchClient\Query\MatchQuery('firstname', 'cedric', CombiningFactor::MUST);

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addQuery($mockQuery)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
            ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['firstname' => 'cedric']])
        ;
    }
}
