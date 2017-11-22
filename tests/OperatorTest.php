<?php

namespace go1\neo4j_builder\tests;

use go1\neo4j_builder\Neo4jBuilder;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    public function testIntArray()
    {
        $client = new Neo4jBuilder();

        $query = $client->match('u.User')
            ->where('u.id', 'id', 'IN')
            ->setParameter('id', [1, 2, 3, 4])
            ->return(['u.id', 'u.mail'])
            ->execute();

        $this->assertEquals("MATCH u.User WHERE u.id IN [1, 2, 3, 4] RETURN u.id, u.mail", $query);
    }

    public function testStringArray()
    {
        $client = new Neo4jBuilder();

        $query = $client->match('u.User')
            ->where('u.mail', 'mails', 'IN')
            ->setParameter('mails', ['1@example.com', '2@example.com', '3@example.com'])
            ->return(['u.id', 'u.mail'])
            ->execute();

        $this->assertEquals("MATCH u.User WHERE u.mail IN ['1@example.com', '2@example.com', '3@example.com'] RETURN u.id, u.mail", $query);
    }
}
