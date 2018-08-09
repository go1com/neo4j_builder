<?php

namespace go1\neo4j_builder;

use GraphAware\Neo4j\Client\Client;
use GraphAware\Neo4j\Client\Connection\ConnectionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Neo4jBuilder extends Client
{
    const MATCH             = 'MATCH';
    const OPTIONAL_MATCH    = 'OPTIONAL MATCH';
    const WHERE             = 'WHERE';
    const AND_WHERE         = 'AND_WHERE';
    const OR_WHERE          = 'OR_WHERE';
    const WITH              = 'WITH';
    const UNWIND            = 'UNWIND';
    const RETURN            = 'RETURN';
    const ORDER_BY          = 'ORDER BY';
    const SKIP              = 'SKIP';
    const LIMIT             = 'LIMIT';

    private $cyphers;
    private $context;

    public function __construct(ConnectionManager $connectionManager = null, EventDispatcherInterface $eventDispatcher = null)
    {
        if ($connectionManager) {
            parent::__construct($connectionManager, $eventDispatcher);
        }
    }

    public function setConnectionManager(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function match(string $cypher)
    {
        return $this->add(self::MATCH, $cypher);
    }

    public function optionalMatch(string $cypher)
    {
        return $this->add(self::OPTIONAL_MATCH, $cypher);
    }

    public function where(string $name, string $contextName = '', string $op = '=')
    {
        $cypher = $contextName ? "({$name} {$op} {{$contextName}})" : $name;
        return $this->add(self::WHERE, $cypher);
    }

    public function andWhere(string $name, string $contextName = '', string $op = '=')
    {
        $cypher = $contextName ? "({$name} {$op} {{$contextName}})" : $name;
        return $this->add(self::AND_WHERE, $cypher);
    }

    public function orWhere(string $name, string $contextName = '', string $op = '=')
    {
        $cypher = $contextName ? "({$name} {$op} {{$contextName}})" : $name;
        return $this->add(self::OR_WHERE, $cypher);
    }

    public function with(array $variables)
    {
        return $this->add(self::WITH, implode(', ', $variables));
    }

    public function unwind(string $names, string $name)
    {
        $cypher = "$names AS $name";
        return $this->add(self::UNWIND, $cypher);
    }

    public function return(array $vars)
    {
        $cypher = implode(', ', $vars);
        return $this->add(self::RETURN, $cypher);
    }

    public function orderBy(string $name, string $direction)
    {
        $cypher = "$name $direction";
        return $this->add(self::ORDER_BY, $cypher);
    }

    public function skip(int $skip)
    {
        return $this->add(self::SKIP, $skip);
    }

    public function limit(int $limit)
    {
        return $this->add(self::LIMIT, $limit);
    }

    public function add(string $clause, string $cypher)
    {
        switch ($clause) {
            case self::MATCH:
            case self::OPTIONAL_MATCH:
            case self::WITH:
            case self::UNWIND:
            case self::WHERE:
            case self::RETURN:
            case self::ORDER_BY:
            case self::SKIP:
            case self::LIMIT:
                $this->cyphers[] = "\n{$clause} {$cypher}";
                break;

            case self::AND_WHERE:
                $this->cyphers[] = "AND {$cypher}";
                break;

            case self::OR_WHERE:
                $this->cyphers[] = "OR {$cypher}";
                break;

            default:
                $this->cyphers[] = $cypher;
                break;

        }

        return $this;
    }

    public function setParameter(string $name, $value)
    {
        $this->context[$name] = $value;

        return $this;
    }

    public function setParameters(array $parameters, bool $reset = false)
    {
        $reset && $this->resetContext();

        foreach ($parameters as $name => $value) {
            $this->setParameter($name, $value);
        }

        return $this;
    }

    public function setCyphers(array $cyphers, bool $reset = false)
    {
        $reset && $this->resetCyphers();

        foreach ($cyphers as $query) {
            $this->add('', $query);
        }

        return $this;
    }

    public function resetContext()
    {
        $this->context = [];
    }

    public function resetCyphers()
    {
        $this->cyphers = [];
    }

    public function getQuery(): string
    {
        return implode(' ', $this->cyphers);
    }

    public function execute()
    {
        if ($this->connectionManager) {
            return parent::run($this->getQuery(), $this->context);
        }

        return $this->__toString();
    }

    public function __toString()
    {
        $query = $this->getQuery();
        $search = array_map(function($key) {
            return "{{$key}}";
        }, array_keys($this->context));

        $replace = array_map(function($value) {
            return $this->convertValue($value);
        }, array_values($this->context));

        return (string) str_replace($search, $replace, $query);
    }

    private function convertValue($value)
    {
        if (is_numeric($value)) {
            $converted = $value;
        }
        else if (is_array($value)) {
            $formatted = array_map(function($item) {
                if (is_numeric($item)) {
                    return $item;
                }
                else {
                    return "'$item'";
                }
            }, $value);
            $converted = '[' . implode(', ', $formatted) . ']';
        }
        else {
            $converted = "'$value'";
        }

        return $converted;
    }

    public static function orCondition(array $cyphers): string
    {
        return '((' . implode(') OR (', $cyphers) . '))';
    }

    public static function andCondition(array $cyphers): string
    {
        return '((' . implode(') AND (', $cyphers) . '))';
    }
}
