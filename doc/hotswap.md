# Hotswap / Reindexing

It is possible to reindex all your data on a separate temporary index, and only swap it with the real index once everything is ok, by using the [Reindex API](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-reindex.html).

An exemple of code would be

```
    $index = new Index($hosts, $name);
    $indexer = new ObjectIndexer($index);
    // clear and create tmp index
    $index->reloadTmp();
    foreach ($arrayData as $data) {
        // index each data on tmp index
        $indexer->indexTmp($data);
    }
    // only at that point recreate main index, copy values from main, and delete tmp index
    $index->reindexTmpToMain();
```
