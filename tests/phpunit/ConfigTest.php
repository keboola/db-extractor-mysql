<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\DbExtractor\Configuration\MysqlConfigRowDefinition;
use Keboola\DbExtractor\Configuration\NodeDefinition\MysqlDbNode;
use Keboola\DbExtractor\Configuration\NodeDefinition\MysqlTableNodesDecorator;
use Keboola\DbExtractorConfig\Config;
use Keboola\DbExtractorConfig\Configuration\ConfigRowDefinition;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @dataProvider validConfigProvider
     */
    public function testValid(array $config): void
    {
        new Config($config, new ConfigRowDefinition(
            new MysqlDbNode(),
            null,
            null,
            new MysqlTableNodesDecorator(),
        ));
        $this->expectNotToPerformAssertions();
    }

    public function validConfigProvider(): array
    {
        return [
            'no-database' => [
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'port' => 3306,
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
            'empty-database' => [
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'database' => '',
                            'port' => 3306,
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
            'ssl: without keypair' => [
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'database' => '',
                            'port' => 3306,
                            'ssl' => [
                                'enabled' => true,
                            ],
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
            'with-ssh-tunnel' => [
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'database' => '',
                            'port' => 3306,
                            'ssh' => [
                                'enabled' => true,
                                'keys' => [
                                    '#private' => 'anyKey',
                                    'public' => 'anyKey',
                                ],
                                'user' => 'root',
                                'sshHost' => 'sshproxy',
                                'remoteHost' => 'mysql',
                                'remotePort' => '1433',
                                'localPort' => '1234',
                                'maxRetries' => 10,
                            ],
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalid(string $expectedErrorMessage, array $config): void
    {
        $this->expectExceptionMessage($expectedErrorMessage);
        new Config($config, new ConfigRowDefinition(
            new MysqlDbNode(),
            null,
            null,
            new MysqlTableNodesDecorator(),
        ));
    }

    public function invalidConfigProvider(): array
    {
        return [
            'ssl: only cert, missing key' => [
                'Invalid configuration for path "root.parameters.db.ssl": Both "#key" and "cert" must be specified',
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'port' => 3306,
                            'ssl' => [
                                'enabled' => true,
                                'cert' => 'abs',
                            ],
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
            'ssl: only key, missing cert' => [
                'Invalid configuration for path "root.parameters.db.ssl": Both "#key" and "cert" must be specified',
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'port' => 3306,
                            'ssl' => [
                                'enabled' => true,
                                '#key' => 'abs',
                            ],
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
            'ssl: invalid item' => [
                'Unrecognized option "bogus" under "root.parameters.db.ssl". Available options are',
                [
                    'parameters' => [
                        'db' => [
                            'host' => 'mysql',
                            'user' => 'root',
                            '#password' => 'rootpassword',
                            'port' => 3306,
                            'ssl' => [
                                'enabled' => true,
                                'bogus' => '42',
                            ],
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
                        'data_dir' => '/tmp/testDatadir',
                        'extractor_class' => 'MySQL',
                    ],
                ],
            ],
        ];
    }

    public function testPropagateDescriptionsDefaultsToEnabled(): void
    {
        $config = new Config($this->getTableRowConfig(), $this->createRowDefinition());

        $this->assertTrue($config->getParameters()['propagateDescriptions']);
    }

    public function testPropagateDescriptionsCanBeDisabled(): void
    {
        $rawConfig = $this->getTableRowConfig();
        $rawConfig['parameters']['propagateDescriptions'] = false;

        $config = new Config($rawConfig, $this->createRowDefinition());

        $this->assertFalse($config->getParameters()['propagateDescriptions']);
    }

    private function createRowDefinition(): MysqlConfigRowDefinition
    {
        return new MysqlConfigRowDefinition(
            new MysqlDbNode(),
            null,
            null,
            new MysqlTableNodesDecorator(),
        );
    }

    private function getTableRowConfig(): array
    {
        return [
            'parameters' => [
                'db' => [
                    'host' => 'mysql',
                    'user' => 'root',
                    '#password' => 'rootpassword',
                    'port' => 3306,
                ],
                'table' => [
                    'schema' => 'test',
                    'tableName' => 'escaping',
                ],
                'outputTable' => 'in.c-main.escaping',
                'data_dir' => '/tmp/testDatadir',
                'extractor_class' => 'MySQL',
            ],
        ];
    }
}
