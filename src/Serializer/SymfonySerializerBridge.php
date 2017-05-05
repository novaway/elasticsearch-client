<?php

namespace Novaway\ElasticsearchClient\Serializer;

use Elasticsearch\Serializers\SerializerInterface;
use Novaway\ElasticsearchClient\Serializer\Exception\InvalidSerializedObjectPropertyException;
use Novaway\ElasticsearchClient\Serializer\Exception\InvalidSerializedObjectTypeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SymfonySerializerBridge implements SerializerInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * SymfonySerializer constructor.
     */
    public function __construct()
    {
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function serialize($data)
    {
        $isDataArray = is_array($data);
        if(!$isDataArray && !is_object($data)) {
            throw new InvalidSerializedObjectTypeException("Serialized data must be an array or an object");
        }

        $normalizedObject = $isDataArray ? $data : $this->serializer->normalize($data);

        if(isset($normalizedObject['nec_serialized_class'])) {
            throw new InvalidSerializedObjectPropertyException("Serialized object can't have a property/key named 'nec_serialized_class'. This key is reserved to the Novaway\\ElasticsearchClient\\Serializer\\SymfonySerializer component");
        }

        $normalizedObject['nec_serialized_class'] = $isDataArray ? 'array' : get_class($data);

        return $this->serializer->encode($normalizedObject);
    }

    /**
     * @inheritDoc
     */
    public function deserialize($data, $headers)
    {

        return $this->serializer->deserialize($data, Person::class, 'xml');
    }

}
