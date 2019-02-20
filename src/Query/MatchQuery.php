<?php

namespace Novaway\ElasticsearchClient\Query;

//https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-match-query.html
use Webmozart\Assert\Assert;

/**
 * @deprecated use Novaway\ElasticsearchClient\Query\FullText\MatchQuery instead
 */
class MatchQuery extends \Novaway\ElasticsearchClient\Query\FullText\MatchQuery
{
}
