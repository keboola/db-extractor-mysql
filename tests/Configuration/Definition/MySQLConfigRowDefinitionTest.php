<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Configuration\Definition;

use Keboola\MysqlExtractor\Configuration\Config;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigRowDefinition;
use Keboola\MysqlExtractor\Tests\Configuration\__fixtures\ConfigParametersProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class MySQLConfigRowDefinitionTest extends TestCase
{
    public function testCreateConfigWithMinimalParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getConfigRowParametersMinimal()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigRowDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithBasicParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getConfigRowParametersBasic()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigRowDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithExtraParametersSuccessfully(): void
    {
        $parameters = ConfigParametersProvider::getConfigRowParametersBasic();
        $parameters['someExtraKey'] = true;
        $config = new Config(
            ['parameters' => $parameters],
            new MySQLConfigRowDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
        $this->assertArrayHasKey('someExtraKey', $config->getParameters());
    }

    public function testCreateConfigWithDefinedTableAndQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigRowParametersBasic();
        $parameters['query'] = 'SELECT 1';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Both "table" and "query" cannot be set together.');
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigRowDefinition()
        );
    }

    public function testCreateConfigWithoutDefinedTableNorQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigRowParametersBasic();
        unset($parameters['table']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "root.parameters": Either "table" or "query" must be defined.'
        );
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigRowDefinition()
        );
    }

    public function testCreateConfigWithIncrementalFetchingOnAdvancedQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigRowParametersBasic();
        unset($parameters['table']);
        $parameters['query'] = 'SELECT 1';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "root.parameters":'
            . ' Incremental fetching is not supported for advanced queries.'
        );
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigRowDefinition()
        );
    }
}
