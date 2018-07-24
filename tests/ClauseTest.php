<?php

namespace go1\neo4j_builder\tests;

use go1\neo4j_builder\Neo4jBuilder;
use PHPUnit\Framework\TestCase;

class ClauseTest extends TestCase
{
    public function testSimpleCase()
    {
        $client = new Neo4jBuilder();

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

        $this->assertEquals("MATCH u.User WHERE u.id = 10 AND u.mail = 'abc@go1.com' RETURN u.id, u.mail SKIP 0 LIMIT 10", $query);
    }

    public function testUnwindCase()
    {
        $client = new Neo4jBuilder();

        $query = $client->match('u.User')
            ->where('u.id', 'ids', 'IN')
            ->andWhere('u.mail', 'mail')
            ->with(['collect(u.uid)'],  'rows1')
            ->setParameters([
                'ids'   => [1, 2, 3, 4, 5],
                'mail' => 'abc@go1.com'
            ])
            ->match('u.User')
            ->where('u.id', 'idsRows', 'IN')
            ->with(['rows1 + collect(u.uid)'],  'rows')
            ->setParameters([
                'idsRows'   => [10, 11]
            ])
            ->unwind('rows', 'uid')
            ->match('u.User {id: {uid}}')
            ->return(['u.id', 'u.mail'])
            ->skip(0)
            ->limit(10)
            ->execute();

        $this->assertEquals("MATCH u.User WHERE u.id IN [1, 2, 3, 4, 5] AND u.mail = 'abc@go1.com' WITH collect(u.uid) AS rows1 MATCH u.User WHERE u.id IN [10, 11] WITH rows1 + collect(u.uid) AS rows UNWIND rows AS uid MATCH u.User {id: {uid}} RETURN u.id, u.mail SKIP 0 LIMIT 10", $query);
    }

    public function testAddCypher()
    {
        $client = new Neo4jBuilder();

        $query = $client->add('', "MATCH u.User WHERE u.id = {uid} RETURN u")
            ->setParameter('uid', 10)
            ->execute();

        $this->assertEquals("MATCH u.User WHERE u.id = 10 RETURN u", $query);
    }
}
