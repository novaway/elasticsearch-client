# Novaway ElasticSearch Client

A lightweight PHP 7.0+ client for Elasticsearch, providing features over [Elasticsearch-PHP](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)

## Compatibility

This branch is tested and compatible with ElasticSearch 6.*

The compatibility with ElasticSearch 5.* is supported, and should work, but is to be considered hazardous.

## Installation

Install using [composer](https://getcomposer.org):

```shell
$ composer require novaway/elasticsearch-client
```

## Usage

### Create an index

The first thing you'll need to do to use this library is to instatiate an index. This will be the keystone of the client.

```php
$index = new \Novaway\ElasticsearchClient\Index(
	['127.0.0.1:9200'],  	# elasticsearch hosts
	'main_index',				# index name
	[
        'settings' => [
            'number_of_shards' => 3,
            'number_of_replicas' => 2
        ],
        'mappings' => [
            'my_type' => [
                '_source' => [
                    'enabled' => true
                ],
                'properties' => [
                    'first_name' => [
                        'type' => 'string',
                        'analyzer' => 'standard'
                    ],
                    'age' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ]
    ]    
);
```

### Index an object

In order to be searched, objects should be indexed as a serialized version. In order to be indexed, Object should implement `\Novaway\ElasticsearchClient\Indexable` interface.

By default, objects are serialized with [Elasticsearch-PHP's SmartSerializer](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_serializers.html#_smartserializer), but you can choose to [use a custom serializer](doc/working-with-a-custom-serializer.md).

```php
$objectIndexer = new \Novaway\ElasticsearchClient\ObjectIndexer($index);
$objectIndexer->index($object, 'my_type');
```

#### Remove an object from index

To remove an object from the index, the process is still 

```php
$objectIndexer = new \Novaway\ElasticsearchClient\ObjectIndexer($index);
$objectIndexer->remove($object, 'my_type');

// Alternatively, you can remove an indexed object knowing only it's ID.
$objectIndexer->removeById($objectId, 'my_type');
```

### Search the index

#### Basic match query

First, create a `QueryExecutor`.

```php
$queryExecutor = new \Novaway\ElasticsearchClient\QueryExecutor($index);
```

Use the `QueryBuilder` to build your query and execute it.

```php
use Novaway\ElasticsearchClient\Query\CombiningFactor;

$queryBody = QueryBuilder::createNew()
					->match('first_name', 'John', CombiningFactor::MUST)
					->getQueryBody()
;
$queryExecutor->execute($queryBody, 'my_type');
```

The `QueryBuilder` allow you to define a limit and an offset for a search result, and choose the minimum score to display.

```php
const MIN_SCORE = 0.4;
const OFFSET = 0;
const LIMIT = 10;

$queryBuilder = QueryBuilder::createNew(0, 10, 0.3);
```

#### Advanced Querying

This client provide several ways to improve querying :

- Filtering *(missing documentation)*
- [Aggregations](doc/aggregation.md)
- Result Formating *(missing documentation)*


### Clear the index

You might want, for some reason, to purge an index. The `reload` method drops and recreates the index.

```php
$index->reload();
```

### Hotswapping

You will want to reindex all your data sometimes.

It is possible to do it without downtime using the hotswap mechanisme

```php
$index->hotswapToTmp();
// at that point, all your search request will go to the tmp index, and your create/delete will go to the main index
// when your are done reindexing your data, simply call 
$index->hotswapToMain()

```


## Recommended usage with Symfony

If you are using this library in a symfony project, we recommend to use it as service.

```yml
# services.yml
parameters:
    myapp.search.myindex.config:
        settings:
            number_of_shards : 1
            number_of_replicas : 1
        mappings:
            my_type:
                _source : { enabled : true }
                properties:
                    first_name:
                        type: string
                        analyzer: standard
                    age:
                        type: integer
                        
services:
    myapp.search.index:
        class: Novaway\ElasticsearchClient\Index
        arguments:
            - ['127.0.0.1:9200'] #define it in the parameter.yml file
            - 'myapp_myindex_%kernel.environment%'
            - 'myapp.search.myindex.config'

    myapp.search.object_indexer:
        class: Novaway\ElasticsearchClient\ObjectIndexer
        arguments:
            - '@myapp.search.index'

    myapp.search.query_executor:
        class: Novaway\ElasticsearchClient\QueryExecutor
        arguments:
            - '@myapp.search.index'
```

Then you'll only have to work with the `myapp.search.object_indexer` and `myapp.search.query_executor` services.

## Testing

A testing environment is provided using a dockerized version of elasticsearch.

Testing is done using the [Atoum](http://atoum.org/) framework for unit testing and the [Behat](http://behat.org/en/latest/) framework for behavior testing.

A `Makefile` provide useful commands for testing, so you can run the full test suite by running :
```sh
$ make test
```

## License

This library is published under [MIT license](LICENSE)
