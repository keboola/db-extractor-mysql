<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Configuration\Definition;

use Keboola\MysqlExtractor\Configuration\Config;
use Keboola\MysqlExtractor\Tests\Configuration\__fixtures\ConfigParametersProvider;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigActionDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class MySQLConfigActionDefinitionTest extends TestCase
{
    public function testCreateConfigWithBasicDbParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersBasic()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithMinimalDbParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersMinimal()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithExtraParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersExtra()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );

        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithMinimalSslParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersMinimalWithSslMinimal()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithBasicSslParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersMinimalWithSslBasic()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithMinimalSshParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersMinimalWithSshMinimal()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithBasicSshParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getDbParametersMinimalWithSshBasic()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithNoDbParametersThrowsException(): void
    {
        $rawConfig = ['parameters' => ['db' => []]];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "user" at path "root.parameters.db" must be configured.');
        new Config(
            $rawConfig,
            new MySQLConfigActionDefinition()
        );
    }
}
