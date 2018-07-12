<?php
namespace Novaway\ElasticsearchClient;


interface Clause
{
    /**
     * Return a JSON formatted representation of the clause, tu use in elasticsearch
     *
     * @return array
     */
    public function formatForQuery(): array;
    /**
     * Return the key under which the clause will be stored in the query
     *
     * @return string
     */
    public function getCombiningFactor(): string;
}
