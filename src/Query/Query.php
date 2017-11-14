<?php


namespace Novaway\ElasticsearchClient\Query;


interface Query
{
    public function formatForQuery(): array;
}
