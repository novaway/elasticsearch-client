## Aggregations

Aggregations are a powerful elasticsearch feature described as followed :

> An aggregation can be seen as a unit-of-work that builds analytic information over a set of documents. The context of the execution defines what this document set is (e.g. a top-level aggregation executes within the context of the executed query/filters of the search request).

More [on the official documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html)

### Querying with aggregations

```php
// Set limit to 0 if you only want to fetch aggregates
// No matter the number of fetched results, aggregates are always built over all query hits
$qb = \Novaway\ElasticsearchClient\Query\QueryBuilder::createNew(QueryBuilder::DEFAULT_OFFSET, 0);

$qb->addFilter(new TermFilter('make', 'BMW'));

$qb->addAggregation(new Aggregation('models', 'terms', 'model'));
$qb->addAggregation(new Aggregation('average_mileage', 'avg', 'mileage'));
$qb->addAggregation(new Aggregation('max_horsepower', 'max', 'horsePower'));
$qb->addAggregation(new Aggregation('histogram_horsepower', 'histogram', 'horsePower', ['interval' => '50']));
```

### Result

Once the query is built, you should be able to execute it with a `QueryExecutor` instance. 

```php
$queryExecutor = new \Novaway\ElasticsearchClient\QueryExecutor($carDealerIndex);
$result = $queryExecutor->execute($qb->getQueryBody(), 'cars');
```

The aggregations are available in the `Result` object via the `Result::aggregations()` function. The above example would give something like this :

```php
[
	'models' => ['1', '2 Active Tourer', '2 Gran Tourer', '4 Coupé', '7 Sedan', 'X1', 'X3', 'M6 Coupé'],
	'average_mileage' => 32684,
	'max_horsepower' => 540,
	'histo_horsepower' => [
		[ 'key' => 110, 'doc_count' => 2 ],
		[ 'key' => 160, 'doc_count' => 9 ],
		[ 'key' => 210, 'doc_count' => 16 ],
		[ 'key' => 260, 'doc_count' => 11 ],
		[ 'key' => 310, 'doc_count' => 8 ],
		[ 'key' => 360, 'doc_count' => 2 ],
		[ 'key' => 410, 'doc_count' => 0 ],
		[ 'key' => 460, 'doc_count' => 2 ],
		[ 'key' => 510, 'doc_count' => 1 ],
	]
]
```

You can use plenty of other aggregation methods detailed [on the official documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html).
