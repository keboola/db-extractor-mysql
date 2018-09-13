<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\Csv\CsvFile;
use Keboola\Temp\Temp;
use PDO;

class RetryTest extends AbstractMySQLTest
{
    public function setUp(): void
    {
        $this->dataDir = __DIR__ . '/../../data';
        // intentionally don't call parent, we use a different PDO connection
    }

    public function testRunMainRetry(): void
    {
        $config = $this->getConfig(self::DRIVER, 'json');
        $config['parameters']['db']['user'] = getenv('TEST_RDS_USERNAME');
        $config['parameters']['db']['#password'] = getenv('TEST_RDS_PASSWORD');
        $config['parameters']['db']['host'] = getenv('TEST_RDS_HOST');
        $config['parameters']['db']['database'] = 'odin4test';
        $config['parameters']['db']['port'] = '3306';
        $config['parameters']['tables'] = [[
            'id' => 1,
            'name' => 'sales',
            'query' => 'SELECT * FROM sales',
            'outputTable' => 'in.c-main.sales',
            'incremental' => false,
            'primaryKey' => null,
            'enabled' => true,
            'advancedMode' => true,
        ]];
        $app = $this->createApplication($config);
        $dbConfig = $config['parameters']['db'];

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8",
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database']
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        ];
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['#password'], $options);

        $temp = new Temp();
        $temp->initRunFolder();
        $sourceFileName = $temp->getTmpFolder() . 'large.csv';
        $csv = new CsvFile($sourceFileName);
        $csv->writeRow(["usergender", "usercity", "usersentiment", "zipcode", "sku", "createdat", "category"]);
        $rowCount = 200000;
        for ($i = 0; $i < $rowCount - 1; $i++) { // -1 for the header
            $csv->writeRow([uniqid('g'), "The Lakes", "1", "89124", "ZD111402", "2013-09-23 22:38:30", uniqid('c')]);
        }
        $this->createTextTable($csv, 'sales', 'odin4test');
        //exec('php ' . __DIR__ . '/../../killerRabbit.php 1', $output, $ret);
        //var_export($output);

        // exec async
        //exec('php ' . __DIR__ . '/../../killerRabbit.php 1 > /dev/null &');
        exec('php ' . __DIR__ . '/../../killerRabbit.php 1 > NUL');
        $result = $app->run();

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv.manifest');
        $this->assertEquals($rowCount, count(file($outputCsvFile)));
        $this->assertFileEquals($sourceFileName, $outputCsvFile);
    }
}