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
        $expected = "\nMATCH u.User \nWHERE (u.id = 10) AND (u.mail = 'abc@go1.com') \nRETURN u.id, u.mail \nSKIP 0 \nLIMIT 10";
        $this->assertEquals($expected, $query);
    }

    public function testUnwindCase()
    {
        $client = new Neo4jBuilder();

        $query = $client->match('u.User')
            ->where('u.id', 'ids', 'IN')
            ->andWhere('u.mail', 'mail')
            ->with(['collect(u.uid) AS rows1'])
            ->setParameters([
                'ids'   => [1, 2, 3, 4, 5],
                'mail' => 'abc@go1.com'
            ])
            ->match('u.User')
            ->where('u.id', 'idsRows', 'IN')
            ->with(['rows1 + collect(u.uid) AS rows'])
            ->setParameters([
                'idsRows'   => [10, 11]
            ])
            ->unwind('rows', 'uid')
            ->match('u.User {id: {uid}}')
            ->return(['u.id', 'u.mail'])
            ->skip(0)
            ->limit(10)
            ->execute();

        $this->assertEquals("\nMATCH u.User \nWHERE (u.id IN [1, 2, 3, 4, 5]) AND (u.mail = 'abc@go1.com') \nWITH collect(u.uid) AS rows1 \nMATCH u.User \nWHERE (u.id IN [10, 11]) \nWITH rows1 + collect(u.uid) AS rows \nUNWIND rows AS uid \nMATCH u.User {id: {uid}} \nRETURN u.id, u.mail \nSKIP 0 \nLIMIT 10", $query);
    }

    public function testAddCypher()
    {
        $client = new Neo4jBuilder();

        $query = $client->add('', "MATCH u.User WHERE u.id = {uid} RETURN u")
            ->setParameter('uid', 10)
            ->execute();

        $this->assertEquals("MATCH u.User WHERE u.id = 10 RETURN u", $query);
    }

    public function testCondition()
    {
        $client = new Neo4jBuilder();
        $query = $client->match("u.User")
            ->where('u.uid', 'uid')
            ->andWhere(
                Neo4jBuilder::orCondition([
                    'u.mail = {mail1}',
                    'u.mail = {mail2}',
                ]))
            ->setParameters([
                'uid'   => 10,
                'mail1' => 'mail1@example.com',
                'mail2' => 'mail2@example.com',
            ])
            ->return(['u'])
            ->execute();

        $this->assertEquals("\nMATCH u.User \nWHERE (u.uid = 10) AND ((u.mail = 'mail1@example.com') OR (u.mail = 'mail2@example.com')) \nRETURN u", $query);

    }
}
