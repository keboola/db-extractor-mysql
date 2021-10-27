<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\DbExtractor\MySQLApplication;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class ConfigTest extends TestCase
{
    /**
     * @dataProvider validConfigProvider
     */
    public function testValid(array $config): void
    {
        new MySQLApplication($config, new TestLogger());
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
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalid(array $config): void
    {
        new MySQLApplication($config, new TestLogger());
        $this->expectExceptionMessage('x');
        $this->expectNotToPerformAssertions();
    }

    public function invalidConfigProvider(): array
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
                            'ssl' => ['cert' => 'abs']
                        ],
                        'query' => 'SELECT * FROM escaping',
                        'outputTable' => 'in.c-main.escaping',
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
                    ],
                ],
            ],
        ];
    }
}
