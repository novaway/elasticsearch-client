<?php


namespace Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos;


class DesindexableObject extends IndexableObject
{
    public function shouldBeIndexed(): bool
    {
        return false;
    }
}
