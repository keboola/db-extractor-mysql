<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Configuration;

use Keboola\MysqlExtractor\Configuration\Config;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigActionDefinition;
use Keboola\MysqlExtractor\Configuration\SslParameters;
use Keboola\MysqlExtractor\Tests\Configuration\__fixtures\ConfigParametersProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testIsNetworkCompressionEnabled(): void
    {
        $config = new Config(
            ['parameters' => ConfigParametersProvider::getDbParametersBasic()],
            new MySQLConfigActionDefinition()
        );

        $this->assertTrue($config->isNetworkCompressionEnabled());
    }

    public function testGetSslParametersSuccessfully(): void
    {
        $config = new Config(
            ['parameters' => ConfigParametersProvider::getDbParametersMinimalWithSslBasic()],
            new MySQLConfigActionDefinition()
        );

        $this->assertInstanceOf(SslParameters::class, $config->getSslParameters());
    }

    public function testGetSslParametersReturnsNullWhenUndefined(): void
    {
        $config = new Config(
            ['parameters' => ConfigParametersProvider::getDbParametersMinimal()],
            new MySQLConfigActionDefinition()
        );
        $this->assertNull($config->getSslParameters());
    }
}
