<?php

namespace Novaway\ElasticsearchClient;

class ObjectIndexer
{
    /** @var Index */
    private $index;

    /**
     * ObjectIndexer constructor.
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * @param Indexable $object
     * @param string $type
     * @param string $type
     */
    public function index(Indexable $object, $type)
    {
        $params = [];
        $params['type'] = $type;
        $params['id'] = $object->getId();

        if (!$object->shouldBeIndexed()) {
            return;
        }

        $params['body'] = $object->toArray();
        $this->index->index($params);
    }

    /**
     * @param Indexable $object
     * @param string $type
     * @param string $type
     */
    public function remove(Indexable $object, $type)
    {
        $this->removeById($object->getId(), $type);
    }

    /**
     * @param Indexable $object
     * @param string $type
     * @param string $type
     */
    public function removeById(string $objectId, $type)
    {
        $params = [];
        $params['type'] = $type;
        $params['id'] = $objectId;

        $this->index->delete($params);
    }
}
