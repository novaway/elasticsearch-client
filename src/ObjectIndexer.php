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
     */
    public function index(Indexable $object, string $type = "_doc")
    {
        $params = $this->getIndexParams($object, $type);
        $this->index->index($params);
    }

    public function indexTmp(Indexable $object, string $type = "_doc")
    {
        $params = $this->getIndexParams($object, $type);
        $this->index->indexTmp($params);
    }

    /**
     * @param Indexable $object
     * @param string $type
     */
    public function remove(Indexable $object, $type)
    {
        $this->removeById($object->getId(), $type);
    }

    /**
     * @param Indexable $object
     * @param string $type
     */
    public function removeById(string $objectId, $type)
    {
        $params = [];
        $params['type'] = $type;
        $params['id'] = $objectId;

        $this->index->delete($params);
    }

    private function getIndexParams(Indexable $object, string $type = "_doc")
    {
        $params = [];
        $params['type'] = $type;
        $params['id'] = $object->getId();

        if (!$object->shouldBeIndexed()) {
            return;
        }

        $params['body'] = $object->toArray();

        return $params;
    }
}
