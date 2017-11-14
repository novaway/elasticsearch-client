<?php


namespace Novaway\ElasticsearchClient\Query;


interface Query extends Clause
{
    public function formatForQuery(): array;
}
