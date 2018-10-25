<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Configuration\__fixtures;

class ConfigParametersProvider
{
    public static function getConfigParametersBasic(): array
    {
        return [
            'db' => self::getDbNodeMinimal(),
            'tables' => [
                [
                    'id' => 1,
                    'name' => 'table_name',
                    'columns' => ['a', 'b'],
                    'table' => [
                        'schema' => 'database_name',
                        'tableName' => 'table_name',
                    ],
                    'outputTable' => 'out-name',
                    'incremental' => true,
                    'enabled' => true,
                    'primaryKey' => ['a'],
                    'retries' => 5,
                    'advancedMode' => false,
                ],
            ],
        ];
    }

    public static function getConfigParametersMinimal(): array
    {
        return [
            'db' => self::getDbNodeMinimal(),
            'tables' => [
                [
                    'id' => 1,
                    'name' => 'table_name',
                    'query' => 'SELECT 1',
                    'outputTable' => 'out-name',
                ],
            ],
        ];
    }

    public static function getConfigRowParametersBasic(): array
    {
        return [
            'db' => self::getDbNodeMinimal(),
            'id' => 1,
            'name' => 'test',
            'table' => [
                'schema' => 'test_db',
                'tableName' => 'table_name',
            ],
            'columns' => ['a', 'b'],
            'outputTable' => 'out-table',
            'incremental' => true,
            'incrementalFetchingColumn' => 'a',
            'incrementalFetchingLimit' => 100,
            'primaryKey' => ['a'],
            'retries' => 5,
            'advancedMode' => false,
        ];
    }

    public static function getConfigRowParametersMinimal(): array
    {
        return [
            'db' => self::getDbNodeMinimal(),
            'outputTable' => 'out-table',
            'query' => 'SELECT 1',
        ];
    }

    public static function getDbParametersBasic(): array
    {
        return ['db' => self::getDbNodeBasic()];
    }

    public static function getDbParametersExtra(): array
    {
        $dbParameters = self::getDbNodeBasic();
        $dbParameters['extraKey'] = true;
        return $dbParameters;
    }

    public static function getDbParametersMinimal(): array
    {
        return ['db' => self::getDbNodeMinimal()];
    }

    public static function getDbParametersMinimalWithSslMinimal(): array
    {
        $dbNode = self::getDbNodeMinimal();
        $dbNode['ssl'] = [];

        return ['db' => $dbNode];
    }

    public static function getDbParametersMinimalWithSslBasic(): array
    {
        $dbNode = self::getDbNodeMinimal();
        $dbNode['ssl'] = self::getSslNodeBasic();

        return ['db' => $dbNode];
    }

    public static function getDbParametersMinimalWithSshMinimal(): array
    {
        $dbNode = self::getDbNodeMinimal();
        $dbNode['ssh'] = self::getSshNodeMinimal();

        return ['db' => $dbNode];
    }

    public static function getDbParametersMinimalWithSshBasic(): array
    {
        $dbNode = self::getDbNodeMinimal();
        $dbNode['ssh'] = self::getSshNodeBasic();
        return ['db' => $dbNode];
    }

    private static function getDbNodeBasic(): array
    {
        return [
            'host' => 'hostname',
            'port' => '10002',
            'user' => 'username',
            '#password' => 'pw',
            'database' => 'schema',
            'networkCompression' => true,
        ];
    }

    private static function getDbNodeMinimal(): array
    {
        return ['user' => 'username'];
    }

    private static function getSslNodeBasic(): array
    {
        return [
            'enabled' => true,
            'ca' => 'CA',
            'cert' => 'certificate',
            'key' => 'key',
            'cipher' => 'passphrase',
        ];
    }

    private static function getSshNodeBasic(): array
    {
        return [
            'enabled' => true,
            'keys' => [
                'private' => 'private',
                '#private' => 'private',
                'public' => 'public',
            ],
            'sshHost' => 'hostname',
            'sshPort' => 22,
            'remoteHost' => 'remote.hostname',
            'remotePort' => 33036,
            'user' => 'username',
        ];
    }

    private static function getSshNodeMinimal(): array
    {
        return ['sshHost' => 'hostname'];
    }
}
