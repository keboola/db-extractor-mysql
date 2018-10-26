<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration\Definition;

use Keboola\DbExtractorCommon\Configuration\Definition\BaseExtractorConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BaseConfigDefinition extends BaseExtractorConfigDefinition
{
    protected function getDbNode(): ArrayNodeDefinition
    {
        // @formatter:off
        $node = parent::getDbNode();
        $node
            ->children()
                ->append($this->getSslNode())
                ->booleanNode('networkCompression')
                    ->defaultValue(false)
                ->end()
            ->end();
        // @formatter:on

        return $node;
    }
    public function getSslNode(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('ssl');

        // @formatter:off
        $node
            ->children()
            ->booleanNode('enabled')->end()
            ->scalarNode('ca')->end()
            ->scalarNode('cert')->end()
            ->scalarNode('key')->end()
            ->scalarNode('cipher')->defaultValue('')->end()
            ->booleanNode('verifyServerCert')->defaultTrue()->end()
            ->end();
        // @formatter:on
        return $node;
    }
}
