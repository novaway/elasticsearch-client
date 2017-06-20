<?php

namespace Test\Unit\Novaway\ElasticsearchClient;

use atoum\test;
use Novaway\ElasticsearchClient\Query\Result;

class QueryExecutor extends test
{
    public function testExecuteQuery()
    {
        $this
            ->given($indexMock = $this->getIndexMock())

            ->and($this->newTestedInstance($indexMock))
            ->if($result = $this->testedInstance->execute(['foo' => 'bar'], 'my_type'))
            ->then

            ->mock($indexMock)
                ->call('search')
            ->once()
                        ->withArguments([
                            'type' => 'my_type',
                            'body' => ['foo' => 'bar'],
                        ])
            ->array($result->hits())->isEqualTo(['riri' => 'fifi'])
        ;
    }

    public function textExecuteWithResultTransformer()
    {
        $this
            ->given($indexMock = $this->getIndexMock())
            ->and(
                $transformerMock = new \mock\Novaway\ElasticsearchClient\Query\ResultTransformer(),
                $transformerMock->getMockController()->formatResult = new Result(1, ['baz' => 'inga'])
            )

            ->and($this->newTestedInstance($indexMock))
            ->if($result = $this->testedInstance->execute(['foo' => 'bar'], 'my_type', $transformerMock))
            ->then

            ->mock($indexMock)
            ->call('search')
            ->once()
            ->withArguments([
                'type' => 'my_type',
                'body' => ['foo' => 'bar'],
            ])
            ->array($result->hits())->isEqualTo(['baz' => 'inga'])
        ;
    }

    private function getIndexMock()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $indexMock = new \mock\Novaway\ElasticsearchClient\Index();
        $indexMock->getMockController()->search = new Result(1, ['riri' => 'fifi']);

        return $indexMock;
    }
}
