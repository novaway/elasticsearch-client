<?php

namespace Test\Unit\Novaway\ElasticsearchClient\Query;

use atoum\test;
use Novaway\ElasticsearchClient\Query\CombiningFactor;

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

        $mockFilterColor = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterColor->getMockController()->formatForQuery = ['term' => ['color' => 'blue']];

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter($mockFilterSize),
                $this->testedInstance->addFilter($mockFilterColor)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['must']->object['match_all']->isEqualTo((object)[])
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

        $mockFilterColor = new \mock\Novaway\ElasticsearchClient\Filter\Filter;
        $mockFilterColor->getMockController()->formatForQuery = ['term' => ['color' => 'blue']];

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

        $this
            ->given($this->newTestedInstance())
            ->if(
                $this->testedInstance->addFilter($mockFilterSize),
                $this->testedInstance->match('firstname', 'cedric', CombiningFactor::MUST),
                $this->testedInstance->match('nickname', 'skwi', CombiningFactor::SHOULD)
            )
            ->then
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['must']->notHasKey('match_all')
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array['filter']->array[0]->isEqualTo(['term' => ['size' => 'M']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::MUST]->array[0]->isEqualTo(['match' => ['firstname' => 'cedric']])
            ->array($this->testedInstance->getQueryBody())
                ->array['query']->array['bool']->array[CombiningFactor::SHOULD]->array[0]->isEqualTo(['match' => ['nickname' => 'skwi']])
        ;
    }

}
