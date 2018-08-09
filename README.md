GO1 Neo4j Query Builder for PHP [![Build Status](https://travis-ci.org/go1com/neo4j_builder.svg?branch=master)](https://travis-ci.org/go1com/neo4j_builder)
====

GO1 Neo4j Builder is the most advanced and flexible Neo4j Client for PHP.

## Install

`composer require go1/neo4j-builder`

## Init class

```php
use go1\neo4j_builder\Neo4jBuilder;
use GraphAware\Neo4j\Client\ClientBuilder;

$config = ['client_class' => Neo4jBuilderClient::class];
$builder = ClientBuilder::create($config);
$builder->addConnection('default', 'NEO4J_URL');

$client = $builder->build();
```

## Usage Examples

```php
$query = $client->match('u.User')
    ->where('u.id', 'id')
    ->andWhere('u.mail', 'mail')
    ->setParameters([
        'id'   => 10,
        'mail' => 'abc@go1.com'
    ])
    ->return(['u.id', 'u.mail'])
    ->skip(0)
    ->limit(10)
    ->execute();
```
