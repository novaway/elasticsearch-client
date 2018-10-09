<?php

namespace Test\Functional\Novaway\ElasticsearchClient\Context\Gizmos;

use Novaway\ElasticsearchClient\Indexable;

class IndexableObject implements Indexable
{
    /** @var string */
    private $id;
    /** @var array */
    private $indexableData;

    /**
     * IndexableMyObject constructor.
     * @param string $id
     * @param array $indexableData
     */
    public function __construct($id, array $indexableData)
    {
        $this->id = (string)$id;
        $this->indexableData = array_map(
            function($value) {
                return $value === 'null' ?  null : $value;
            },
            $indexableData
        );
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function shouldBeIndexed(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->indexableData;
    }

}
