<?php
/**
 * @package ex-db-mysql
 * @author Erik Zigo <erik.zigo@keboola.com>
 */

namespace Keboola\DbExtractor\Tests;

use Keboola\Csv\CsvFile;
use Symfony\Component\Yaml\Yaml;
use Nette\Utils;

class MySQLTest extends AbstractMySQLTest
{
    public function testCredentials()
    {
        $config = $this->getConfig();
        $config['action'] = 'testConnection';
        $config['parameters']['tables'] = [];

        $app = $this->createApplication($config);
        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testCredentialsWithoutDatabase()
    {
        $config = $this->getConfig();
        $config['action'] = 'testConnection';
        $config['parameters']['tables'] = [];
        unset($config['parameters']['db']['database']);

        $app = $this->createApplication($config);
        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testRunWithoutTables()
    {
        $config = $this->getConfig();

        $config['parameters']['tables'] = [];

        $app = $this->createApplication($config);
        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    /**
     * @param $configType
     * @dataProvider configTypesProvider
     */
    public function testRunMain($configType)
    {
        $config = $this->getConfig(self::DRIVER, $configType);
        $app = $this->createApplication($config);

        $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');
        $this->createTextTable($csv1);

        $csv2 = new CsvFile($this->dataDir . '/mysql/escaping.csv');
        $this->createTextTable($csv2);

        $result = $app->run();

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][0] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $result['imported'][0] . '.csv.manifest');
        $this->assertFileEquals((string) $csv1, $outputCsvFile);


        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][1] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $result['imported'][1] . '.csv.manifest');
        $this->assertFileEquals((string) $csv2, $outputCsvFile);
    }

    public function testRunWithoutDatabase()
    {
        $config = $this->getConfig();
        $config['action'] = 'testConnection';
        unset($config['parameters']['db']['database']);

        // Add schema to db query
        $config['parameters']['tables'][0]['query'] = "SELECT * FROM test.sales";
        $config['parameters']['tables'][1]['query'] = "SELECT * FROM test.escaping";

        $app = $this->createApplication($config);
        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testCredentialsWithSSH()
    {
        $config = $this->getConfig();
        $config['action'] = 'testConnection';

        $config['parameters']['db']['ssh'] = [
            'enabled' => true,
            'keys' => [
                '#private' => $this->getPrivateKey('mysql'),
                'public' => $this->getEnv('mysql', 'DB_SSH_KEY_PUBLIC')
            ],
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'sshPort' => '22',
            'remoteHost' => 'mysql',
            'remotePort' => '3306',
            'localPort' => '23305',
        ];

        $config['parameters']['tables'] = [];

        $app = $this->createApplication($config);

        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testRunWithSSH()
    {
        $config = $this->getConfig();
        $config['parameters']['db']['ssh'] = [
            'enabled' => true,
            'keys' => [
                '#private' => $this->getPrivateKey('mysql'),
                'public' => $this->getEnv('mysql', 'DB_SSH_KEY_PUBLIC')
            ],
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'localPort' => '23306',
        ];

        $app = $this->createApplication($config);

        $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');
        $this->createTextTable($csv1);

        $csv2 = new CsvFile($this->dataDir . '/mysql/escaping.csv');
        $this->createTextTable($csv2);

        $result = $app->run();

        $sanitizedTable = Utils\Strings::webalize($result['imported'][0], '._');
        $outputCsvFile = $this->dataDir . '/out/tables/' . $sanitizedTable . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $sanitizedTable . '.csv.manifest');
        $this->assertFileEquals((string) $csv1, $outputCsvFile);

        $sanitizedTable = Utils\Strings::webalize($result['imported'][1], '._');
        $outputCsvFile = $this->dataDir . '/out/tables/' . $sanitizedTable . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $sanitizedTable . '.csv.manifest');
        $this->assertFileEquals((string) $csv2, $outputCsvFile);
    }

    public function testUserException()
    {
        $this->setExpectedException('Keboola\DbExtractor\Exception\UserException');

        $config = $this->getConfig('mysql');

        $config['parameters']['db']['host'] = 'nonexistinghost';
        $app = $this->createApplication($config);

        $app->run();
    }

    public function testGetTables()
    {
        // add a table with an auto_increment
        $this->createAutoIncrementTable();

        // add a table with a timestamp using ON UPDATE CURRENT_TIMESTAMP()
        $this->createTimestampTable();

        // add a table to a different schema (should not be fetched)
        $this->createTextTable(
            new CsvFile($this->dataDir . '/mysql/sales.csv'),
            "ext_sales",
            "temp_schema"
        );

        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $app = $this->createApplication($config);

        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('tables', $result);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(4, $result['tables']);

        $expectedData = array (
            0 =>
                array (
                    'name' => 'auto-increment',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '1',
                    'autoIncrement' => '2',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'id',
                                    'type' => 'int',
                                    'primaryKey' => true,
                                    'length' => '10',
                                    'nullable' => false,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                    'extra' => 'auto_increment',
                                    'autoIncrement' => '2',
                                ),
                            1 =>
                                array (
                                    'name' => 'name',
                                    'type' => 'varchar',
                                    'primaryKey' => false,
                                    'length' => '30',
                                    'nullable' => false,
                                    'default' => 'pam',
                                    'ordinalPosition' => '2',
                                ),
                        ),
                ),
            1 =>
                array (
                    'name' => 'escaping',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '7',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'col1',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'col2',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '2',
                                ),
                        ),
                ),
            2 =>
                array (
                    'name' => 'sales',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '100',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'usergender',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'usercity',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '2',
                                ),
                            2 =>
                                array (
                                    'name' => 'usersentiment',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '3',
                                ),
                            3 =>
                                array (
                                    'name' => 'zipcode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '4',
                                ),
                            4 =>
                                array (
                                    'name' => 'sku',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '5',
                                ),
                            5 =>
                                array (
                                    'name' => 'createdat',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '6',
                                ),
                            6 =>
                                array (
                                    'name' => 'category',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '7',
                                ),
                            7 =>
                                array (
                                    'name' => 'price',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '8',
                                ),
                            8 =>
                                array (
                                    'name' => 'county',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '9',
                                ),
                            9 =>
                                array (
                                    'name' => 'countycode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '10',
                                ),
                            10 =>
                                array (
                                    'name' => 'userstate',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '11',
                                ),
                            11 =>
                                array (
                                    'name' => 'categorygroup',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '12',
                                ),
                        ),
                ),
            3 =>
                array (
                    'name' => 'timestamp',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '1',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'name',
                                    'type' => 'varchar',
                                    'primaryKey' => false,
                                    'length' => '30',
                                    'nullable' => false,
                                    'default' => 'pam',
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'timestamp',
                                    'type' => 'timestamp',
                                    'primaryKey' => false,
                                    'length' => null,
                                    'nullable' => false,
                                    'default' => 'CURRENT_TIMESTAMP',
                                    'ordinalPosition' => '2',
                                    'extra' => 'on update CURRENT_TIMESTAMP',
                                ),
                        ),
                    'timestampUpdateColumn' => 'timestamp',
                ),
        );
        $this->assertEquals($expectedData, $result['tables']);
    }

    public function testGetTablesNoDatabase()
    {
        // add a table with an auto_increment
        $this->createAutoIncrementTable();

        // add a table with a timestamp using ON UPDATE CURRENT_TIMESTAMP()
        $this->createTimestampTable();

        // add a table to a different schema
        $this->createTextTable(
            new CsvFile($this->dataDir . '/mysql/sales.csv'),
            "ext_sales",
            "temp_schema"
        );

        $config = $this->getConfig();
        $config['parameters']['tables'] = [];
        unset($config['parameters']['db']['database']);
        $config['action'] = 'getTables';
        $app = $this->createApplication($config);

        $result = $app->run();

        $this->assertGreaterThanOrEqual(5, count($result['tables']));

        $expectedTables = array (
            0 =>
                array (
                    'name' => 'ext_sales',
                    'schema' => 'temp_schema',
                    'type' => 'BASE TABLE',
                    'rowCount' => '100',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'usergender',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'usercity',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '2',
                                ),
                            2 =>
                                array (
                                    'name' => 'usersentiment',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '3',
                                ),
                            3 =>
                                array (
                                    'name' => 'zipcode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '4',
                                ),
                            4 =>
                                array (
                                    'name' => 'sku',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '5',
                                ),
                            5 =>
                                array (
                                    'name' => 'createdat',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '6',
                                ),
                            6 =>
                                array (
                                    'name' => 'category',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '7',
                                ),
                            7 =>
                                array (
                                    'name' => 'price',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '8',
                                ),
                            8 =>
                                array (
                                    'name' => 'county',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '9',
                                ),
                            9 =>
                                array (
                                    'name' => 'countycode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '10',
                                ),
                            10 =>
                                array (
                                    'name' => 'userstate',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '11',
                                ),
                            11 =>
                                array (
                                    'name' => 'categorygroup',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '12',
                                ),
                        ),
                ),
            1 =>
                array (
                    'name' => 'auto-increment',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '1',
                    'autoIncrement' => '2',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'id',
                                    'type' => 'int',
                                    'primaryKey' => true,
                                    'length' => '10',
                                    'nullable' => false,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                    'extra' => 'auto_increment',
                                    'autoIncrement' => '2',
                                ),
                            1 =>
                                array (
                                    'name' => 'name',
                                    'type' => 'varchar',
                                    'primaryKey' => false,
                                    'length' => '30',
                                    'nullable' => false,
                                    'default' => 'pam',
                                    'ordinalPosition' => '2',
                                ),
                        ),
                ),
            2 =>
                array (
                    'name' => 'escaping',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '7',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'col1',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'col2',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '2',
                                ),
                        ),
                ),
            3 =>
                array (
                    'name' => 'sales',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '100',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'usergender',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'usercity',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '2',
                                ),
                            2 =>
                                array (
                                    'name' => 'usersentiment',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '3',
                                ),
                            3 =>
                                array (
                                    'name' => 'zipcode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '4',
                                ),
                            4 =>
                                array (
                                    'name' => 'sku',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '5',
                                ),
                            5 =>
                                array (
                                    'name' => 'createdat',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '6',
                                ),
                            6 =>
                                array (
                                    'name' => 'category',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '7',
                                ),
                            7 =>
                                array (
                                    'name' => 'price',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '8',
                                ),
                            8 =>
                                array (
                                    'name' => 'county',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '9',
                                ),
                            9 =>
                                array (
                                    'name' => 'countycode',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '10',
                                ),
                            10 =>
                                array (
                                    'name' => 'userstate',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '11',
                                ),
                            11 =>
                                array (
                                    'name' => 'categorygroup',
                                    'type' => 'text',
                                    'primaryKey' => false,
                                    'length' => '65535',
                                    'nullable' => true,
                                    'default' => null,
                                    'ordinalPosition' => '12',
                                ),
                        ),
                ),
            4 =>
                array (
                    'name' => 'timestamp',
                    'schema' => 'test',
                    'type' => 'BASE TABLE',
                    'rowCount' => '1',
                    'columns' =>
                        array (
                            0 =>
                                array (
                                    'name' => 'name',
                                    'type' => 'varchar',
                                    'primaryKey' => false,
                                    'length' => '30',
                                    'nullable' => false,
                                    'default' => 'pam',
                                    'ordinalPosition' => '1',
                                ),
                            1 =>
                                array (
                                    'name' => 'timestamp',
                                    'type' => 'timestamp',
                                    'primaryKey' => false,
                                    'length' => null,
                                    'nullable' => false,
                                    'default' => 'CURRENT_TIMESTAMP',
                                    'ordinalPosition' => '2',
                                    'extra' => 'on update CURRENT_TIMESTAMP',
                                ),
                        ),
                    'timestampUpdateColumn' => 'timestamp',
                ),
        );
        $this->assertEquals($expectedTables, $result['tables']);
    }

    public function testManifestMetadata()
    {
        $config = $this->getConfig();

        // use just the last table from the config
        unset($config['parameters']['tables'][0]);
        unset($config['parameters']['tables'][1]);

        $app = $this->createApplication($config);

        $result = $app->run();

        $sanitizedTable = Utils\Strings::webalize($result['imported'][0], '._');
        $outputManifest = Yaml::parse(
            file_get_contents($this->dataDir . '/out/tables/' . $sanitizedTable . '.csv.manifest')
        );

        $this->assertArrayHasKey('destination', $outputManifest);
        $this->assertArrayHasKey('incremental', $outputManifest);
        $this->assertArrayHasKey('metadata', $outputManifest);
        $expectedMetadata = [
            'KBC.name' => 'sales',
            'KBC.schema' => 'test',
            'KBC.type' => 'BASE TABLE',
            'KBC.rowCount' => 100
        ];
        $tableMetadata = [];
        foreach ($outputManifest['metadata'] as $i => $metadata) {
            $this->assertArrayHasKey('key', $metadata);
            $this->assertArrayHasKey('value', $metadata);
            $tableMetadata[$metadata['key']] = $metadata['value'];
        }
        $this->assertEquals($expectedMetadata, $tableMetadata);

        $this->assertArrayHasKey('column_metadata', $outputManifest);
        $this->assertCount(4, $outputManifest['column_metadata']);

        $expectedColumnMetadata = [
            'KBC.datatype.type' => 'text',
            'KBC.datatype.basetype' => 'STRING',
            'KBC.datatype.nullable' => true,
            'KBC.datatype.length' => '65535',
            'KBC.primaryKey' => false,
            'KBC.ordinalPosition' => '1'
        ];
        $columnMetadata = [];
        foreach ($outputManifest['column_metadata']['usergender'] as $metadata) {
            $this->assertArrayHasKey('key', $metadata);
            $this->assertArrayHasKey('value', $metadata);
            $columnMetadata[$metadata['key']] = $metadata['value'];
        }
        $this->assertEquals($expectedColumnMetadata, $columnMetadata);
    }

    public function testSchemaNotEqualToDatabase()
    {
        $this->createTextTable(
            new CsvFile($this->dataDir . '/mysql/sales.csv'),
            "ext_sales",
            "temp_schema"
        );

        $config = $this->getConfig();

        $config['parameters']['tables'][2]['table'] = ['schema' => 'temp_schema', 'tableName' => 'ext_sales'];
        unset($config['parameters']['tables'][0]);
        unset($config['parameters']['tables'][1]);

        try {
            $app = $this->createApplication($config);
            $app->run();
            $this->fail('table schema and database mismatch');
        } catch (\Keboola\DbExtractor\Exception\UserException $e) {
            $this->assertStringStartsWith("Invalid Configuration", $e->getMessage());
        }
    }

    public function testThousandsOfTables()
    {
        $this->markTestSkipped("No need to run this test every time.");
        $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');

        for ($i = 0; $i < 3500; $i++) {
            $this->createTextTable($csv1, "sales_" . $i);
        }

        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $app = $this->createApplication($config);

        $result = $app->run();
        echo "\nThere are " . count($result['tables']) . " tables\n";
    }
}
