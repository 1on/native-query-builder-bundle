# IntaroNativeQueryBuilderBundle #

## About ##

Extension for doctrine entity manager - adds createNativeQueryBuilder method.
A NativeQueryBuilder provides an API that is designed for conditionally constructing a SQL query in several steps.

## Installation ##

Require the bundle in your composer.json file:

````
{
    "require": {
        "intaro/native-query-builder-bundle": "dev-master",
    }
}
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new Intaro\NativeQueryBuilderBundle\IntaroNativeQueryBuilderBundle(),
    );
}
```

Install the bundle:

```
$ composer update intaro/native-query-builder-bundle
```


## Usage ##

Create a NativeQueryBuilder instance:
```php
    $builder = $this->getDoctrine()->getManager()->createNativeQueryBuilder();
```

Simple select:
```php
    $builder->select('user.*')
        ->from('user user')
        ->join('JOIN article article', 'article.user_id = user.id')
        ->where('article.date <= ?', new DateTime())
        ->orderBy('user.name', 'DESC')
        ->limit(20)
        ->page(2);
```

Create resultSetMapping and get results:
```php
    $rsm = new ResultSetMappingBuilder($this->getDoctrine()->getManager());
    $rsm->addRootEntityFromClassMetadata('AsozdGdDataBundle:Planning\PD', 'pd');

    $users = $builder->getQuery($rsm)->getResult();
```

### Methods list ###

```php
class NativeQueryBuilder
{
    // Example $builder->select('user.*');
    // Example $builder->select('user.*, article.id');
    public function select($select)

    // Clears select statement
    public function clearSelect()

    // Example $builder->from('user user');
    public function from($from)

    // Example $builder->join('JOIN article article', 'article.user_id = user.id');
    public function join($table, $joinOn)

    // Example $builder->where('article.date <= ?', new DateTime())
    // Example $builder->where('article.active = TRUE')
    //
    // Example $builder->where('article.active = TRUE', null, true)
    //      ->where('article.date <= ?', new DateTime(), true)
    //      ->where('article.published = TRUE')
    // Result query: WHERE (article.active = TRUE OR article.date <= NOW) AND 'article.published = TRUE'
    public function where($where, $parameter = null, $or = false)

    // Example $builder->orderBy('article.date', 'DESC')
    //      ->orderBy('article.publish_date', 'DESC')
    public function orderBy($field, $direction = 'DESC')

    public function limit($limit)

    public function page($page)

    // If cacheTime = 0 cache is disabled
    // ResetParameters if true - after getQuery all statements (select, from, join, where ...) will be cleared
    public function getQuery(ResultSetMapping $rsm, $cacheTime = self::CACHE_TIME, $resetParameters = true)
```



### More examples ###

Complex example with getting entities count:
```php
    $builder = $this->getDoctrine()->getManager()->createNativeQueryBuilder();
    $builder->from('document document')
        ->join('JOIN action action', 'action.document_id = document.id')
        ->join('JOIN document_type documentType', 'document.type_id = documentType.id');

        if ($type == 'protocol')
        {
            $builder->join('LEFT JOIN protocol protocol', 'protocol.id = action.protocol_id')
                ->where('EXISTS (SELECT 1 FROM protocol_item protocol_item
                        WHERE protocol_item.protocol_id = protocol.id)', null, true)
                ->where('EXISTS (SELECT 1 FROM action_document action_document
                        WHERE action_document.action_id = action.id AND
                            documentType.id = ?)', DocumentType::PROTOCOL, true);
        }

        $rsm = new ResultSetMappingBuilder($this->getDoctrine()->getManager());
        $rsm->addScalarResult('cnt', 'count');
        $itemsCount = $builder->select('count(document.id) as cnt')->getQuery($rsm, 3600, false)->getSingleScalarResult();

        $rsm = new ResultSetMappingBuilder($this->getDoctrine()->getManager());
        $rsm->addRootEntityFromClassMetadata('AsozdGdDataBundle:Planning\document', 'document');
        $items = $builder->clearSelect()->select('document.*')
            ->orderBy('document.start_date', 'DESC')
            ->limit(20)->page(1)->getQuery($rsm, 3600)->getResult();
```