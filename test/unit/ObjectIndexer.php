<?php

namespace Test\Unit\Novaway\ElasticsearchClient;

use atoum\test;

class ObjectIndexer extends test
{
    public function testIndex()
    {
        $this
            ->given($indexMock = $this->getIndexMock())
            ->and($indexableMock = $this->getIndexableMock())

            ->and($this->newTestedInstance($indexMock))

            ->if($this->testedInstance->index($indexableMock, 'my_type'))
            ->then

            ->mock($indexMock)
                ->call('index')
                    ->once()
                        ->withArguments([
                            'type' => 'my_type',
                            'id' => '5',
                            'body' => [
                                'firstname' => 'cedric'
                            ],
                        ])
        ;
    }

    public function testIndexNonIndexable()
    {
        $this
            ->given($indexMock = $this->getIndexMock())
            ->and($indexableMock = $this->getIndexableMock())
            ->and($indexableMock->getMockController()->shouldBeIndexed = false)

            ->and($this->newTestedInstance($indexMock))

            ->if($this->testedInstance->index($indexableMock, 'my_type'))
            ->then

            ->mock($indexMock)
            ->call('index')
            ->never()
        ;
    }

    public function testRemove()
    {
        $this
            ->given($indexMock = $this->getIndexMock())
            ->and($indexableMock = $this->getIndexableMock())

            ->and($this->newTestedInstance($indexMock))

            ->if($this->testedInstance->remove($indexableMock, 'my_type'))
            ->then

            ->mock($indexMock)
            ->call('delete')
            ->once()
            ->withArguments([
                'type' => 'my_type',
                'id' => '5',
            ])
        ;
    }

    public function testRemoveById()
    {
        $this
            ->given($indexMock = $this->getIndexMock())
            ->and($indexableMock = $this->getIndexableMock())

            ->and($this->newTestedInstance($indexMock))

            ->if($this->testedInstance->removeById(9, 'my_type'))
            ->then

            ->mock($indexMock)
            ->call('delete')
            ->once()
            ->withArguments([
                'type' => 'my_type',
                'id' => '9',
            ])
        ;
    }

    private function getIndexMock()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();
        $indexMock = new \mock\Novaway\ElasticsearchClient\Index();

        return $indexMock;
    }

    private function getIndexableMock()
    {
        $indexableMock = new \mock\Novaway\ElasticsearchClient\Indexable();
        $indexableMock->getMockController()->getId = '5';
        $indexableMock->getMockController()->shouldBeIndexed = true;
        $indexableMock->getMockController()->toArray = ['firstname' => 'cedric'];

        return $indexableMock;
    }
}
