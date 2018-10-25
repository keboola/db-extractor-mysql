<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Configuration\Definition;

use Keboola\MysqlExtractor\Configuration\Config;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigDefinition;
use Keboola\MysqlExtractor\Tests\Configuration\__fixtures\ConfigParametersProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class MySQLConfigDefinitionTest extends TestCase
{
    public function testCreateConfigWithMinimalParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getConfigParametersMinimal()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithBasicParametersSuccessfully(): void
    {
        $rawConfig = ['parameters' => ConfigParametersProvider::getConfigParametersBasic()];
        $config = new Config(
            $rawConfig,
            new MySQLConfigDefinition()
        );
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testCreateConfigWithExtraParametersThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigParametersBasic();
        $parameters['tables'][0]['someExtraKey'] = true;

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "someExtraKey" under "root.parameters.tables.0');
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigDefinition()
        );
    }

    public function testCreateConfigWithDefinedTableAndQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigParametersBasic();
        $parameters['tables'][0]['query'] = 'SELECT 1';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Both "table" and "query" cannot be set together.');
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigDefinition()
        );
    }

    public function testCreateConfigWithoutDefinedTableNorQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigParametersBasic();
        unset($parameters['tables'][0]['table']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "root.parameters.tables": Either "table" or "query" must be defined.'
        );
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigDefinition()
        );
    }

    public function testCreateConfigWithIncrementalFetchingOnAdvancedQueryThrowsException(): void
    {
        $parameters = ConfigParametersProvider::getConfigParametersBasic();
        unset($parameters['tables'][0]['table']);
        $parameters['tables'][0]['query'] = 'SELECT 1';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "root.parameters.tables":'
            . ' Incremental fetching is not supported for advanced queries.'
        );
        new Config(
            ['parameters' => $parameters],
            new MySQLConfigDefinition()
        );
    }
}
