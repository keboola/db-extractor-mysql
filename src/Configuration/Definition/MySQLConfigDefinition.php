<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration\Definition;

use Keboola\DbExtractorCommon\Configuration\ConfigDefinitionValidationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class MySQLConfigDefinition extends BaseConfigDefinition
{
    public function getParametersDefinition(): ArrayNodeDefinition
    {
        $rootNode = parent::getParametersDefinition();

        // @formatter:off
        $rootNode
            ->children()
                ->append($this->getDbNode())
                ->append($this->getTablesNode())
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

    protected function getTablesNode(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder();

        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('tables');

        $node
            ->arrayPrototype()
                ->children()
                    ->integerNode('id')
                        ->isRequired()
                        ->min(0)
                    ->end()
                    ->scalarNode('name')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('query')->end()
                    ->arrayNode('columns')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('outputTable')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->append($this->getTableNode())
                    ->booleanNode('incremental')
                        ->defaultValue(false)
                    ->end()
                    ->booleanNode('enabled')
                        ->defaultValue(true)
                    ->end()
                    ->arrayNode('primaryKey')
                        ->prototype('scalar')->end()
                    ->end()
                    ->integerNode('retries')
                        ->min(0)
                    ->end()
                    ->booleanNode('advancedMode')->end()
                ->end()
            ->end();

        $node->validate()
            ->ifTrue(function ($v) {
                foreach ($v as $table) {
                    return ConfigDefinitionValidationHelper::isNeitherQueryNorTableDefined($table);
                }
            })
            ->thenInvalid(ConfigDefinitionValidationHelper::MESSAGE_TABLE_OR_QUERY_MUST_BE_DEFINED)
            ->end();

        $node->validate()
            ->ifTrue(function ($v) {
                foreach ($v as $table) {
                    return ConfigDefinitionValidationHelper::areBothQueryAndTableSet($table);
                }
            })
            ->thenInvalid(ConfigDefinitionValidationHelper::MESSAGE_TABLE_AND_QUERY_CANNOT_BE_SET_TOGETHER)
            ->end();

        $node->validate()
            ->ifTrue(function ($v) {
                foreach ($v as $table) {
                    return isset($table['query']) && $table['incremental'];
                }
            })
            ->thenInvalid(ConfigDefinitionValidationHelper::MESSAGE_CUSTOM_QUERY_CANNOT_BE_FETCHED_INCREMENTALLY)
            ->end();

        return $node;
    }
}
