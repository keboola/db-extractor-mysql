<?php

declare(strict_types=1);

namespace Keboola\ExMySql\Configuration\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MySQLConfigActionDefinition extends BaseConfigDefinition
{
    public function getParametersDefinition(): ArrayNodeDefinition
    {
        $rootNode = parent::getParametersDefinition();

        // @formatter:off
        $rootNode
            ->ignoreExtraKeys(false)
            ->children()
                ->append($this->getDbNode())
            ->end();
        // @formatter:on

        return $rootNode;
    }
}
