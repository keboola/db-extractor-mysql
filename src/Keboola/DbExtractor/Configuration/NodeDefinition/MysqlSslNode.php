<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration\NodeDefinition;

use Keboola\DbExtractorConfig\Configuration\NodeDefinition\SslNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;

class MysqlSslNode extends SslNode
{

    public function init(NodeBuilder $nodeBuilder): void
    {
        parent::init($nodeBuilder);

        // @formatter:off
        $this
            ->validate()
                ->ifTrue(function ($v) { return true;})
                ->thenInvalid('ERR')
            ->end()
        ;
        // @formatter:on
    }
}
