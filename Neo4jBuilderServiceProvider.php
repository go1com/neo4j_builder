<?php

namespace go1\neo4j_builder;

use GraphAware\Neo4j\Client\ClientBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class Neo4jBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Container $c)
    {
        $c['go1.client.graph'] = function (Container $c) {
            $config = ['client_class' => Neo4jBuilder::class, ClientBuilder::TIMEOUT_CONFIG_KEY => 30];
            $builder = ClientBuilder::create($config);
            $builder->addConnection('default', $c['graph_url']);

            if ($c->offsetExists('profiler.do') && $c->offsetGet('profiler.do')) {
                $c['profiler.collectors.neo4j']->attachEventListeners($builder);
            }

            return $builder->build();
        };
    }
}
