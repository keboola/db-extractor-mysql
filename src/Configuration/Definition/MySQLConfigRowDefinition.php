<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration\Definition;

use Keboola\DbExtractorCommon\Configuration\Definition\ConfigRowDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MySQLConfigRowDefinition extends ConfigRowDefinition
{
    public function getParametersDefinition(): ArrayNodeDefinition
    {
        $rootNode = parent::getParametersDefinition();

        // @formatter:off
        $rootNode
            ->children()
                ->booleanNode('advancedMode')->end()
            ->end();
        // @formatter:on

        return $rootNode;
    }

    protected function getDbNode(): ArrayNodeDefinition
    {
        $node = parent::getDbNode();

        // @formatter:off
        $node
            ->children()
                ->booleanNode('networkCompression')->end()
            ->end();
        // @formatter:on

        return $node;
    }
}
