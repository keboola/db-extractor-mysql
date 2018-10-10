<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MySQLConfigDefinition extends BaseConfigDefinition
{
    public function getParametersDefinition(): ArrayNodeDefinition
    {
        $rootNode = parent::getParametersDefinition();

        // @formatter:off
        $rootNode
            ->ignoreExtraKeys(false)
            ->children()
                ->append($this->getDbNode())
                ->append($this->getTablesNode())
            ->end();
        // @formatter:on

        return $rootNode;
    }
}
