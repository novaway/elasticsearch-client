<?php

namespace Novaway\ElasticsearchClient;

use Webmozart\Assert\Assert;

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
     * @param Indexable[] $objects
     * @param string $type
     */
    public function bulkIndex(array $objects, string $type = "_doc"): array
    {
        $params['type'] = $type;

        Assert::allIsInstanceOf($objects, Indexable::class);

        foreach ($objects as $indexable) {
            $key = $indexable->shouldBeIndexed() ? 'index' : 'delete';
            $body[] = [$key => ['_id' => $indexable->getId()]];
            if ($indexable->shouldBeIndexed()) {
                $body[] = $indexable->toArray();
            }
        }
        $params['body'] = $body;
        return $this->index->bulkIndex($params);
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
}
