<?php

namespace Novaway\ElasticsearchClient;

interface Indexable
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return bool
     */
    public function shouldBeIndexed(): bool;

    /**
     * @return array
     */
    public function toArray(): array;
}
