<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Keboola\DbExtractor;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Csv\CsvReader;

class IncrementalFetchingTest extends AbstractMySQLTest
{

    public function testIncrementalFetchingByTimestamp(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = 'timestamp';
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertNotEmpty($result['state']['lastFetchedRow']);

        sleep(2);
        // the next fetch should be empty
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $noNewRowsResult = JsonHelper::decode($stdout);
        $this->assertEquals(1, $noNewRowsResult['imported']['rows']);

        sleep(2);
        //now add a couple rows and run it again.
        $this->pdo->exec('INSERT INTO auto_increment_timestamp (`weird-Name`) VALUES (\'charles\'), (\'william\')');

        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $newResult = JsonHelper::decode($stdout);

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $newResult);
        $this->assertArrayHasKey('lastFetchedRow', $newResult['state']);
        $this->assertGreaterThan(
            $result['state']['lastFetchedRow'],
            $newResult['state']['lastFetchedRow']
        );
        $this->assertEquals(3, $newResult['imported']['rows']);
    }

    public function testIncrementalFetchingByDatetime(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = 'datetime';
        $config['parameters']['table']['tableName'] = 'auto_increment_timestamp';
        $config['parameters']['outputTable'] = 'in.c-main.auto-increment-timestamp';
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertNotEmpty($result['state']['lastFetchedRow']);

        sleep(2);

        // the next fetch should be empty
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $noNewRowsResult = JsonHelper::decode($stdout);

        $this->assertEquals(1, $noNewRowsResult['imported']['rows']);

        sleep(2);
        //now add a couple rows and run it again.
        $this->pdo->exec('INSERT INTO auto_increment_timestamp (`weird-Name`) VALUES (\'charles\'), (\'william\')');

        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $newResult = JsonHelper::decode($stdout);

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $newResult);
        $this->assertArrayHasKey('lastFetchedRow', $newResult['state']);
        $this->assertGreaterThan(
            $result['state']['lastFetchedRow'],
            $newResult['state']['lastFetchedRow']
        );
        $this->assertEquals(3, $newResult['imported']['rows']);
    }

    public function testIncrementalFetchingByAutoIncrement(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = '_weird-I-d';
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertEquals(2, $result['state']['lastFetchedRow']);

        sleep(2);
        // the next fetch should be empty
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $noNewRowsResult = JsonHelper::decode($stdout);

        $this->assertEquals(1, $noNewRowsResult['imported']['rows']);

        sleep(2);
        //now add a couple rows and run it again.
        $this->pdo->exec('INSERT INTO auto_increment_timestamp (`weird-Name`) VALUES (\'charles\'), (\'william\')');

        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $newResult = JsonHelper::decode($stdout);

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $newResult);
        $this->assertArrayHasKey('lastFetchedRow', $newResult['state']);
        $this->assertEquals(4, $newResult['state']['lastFetchedRow']);
        $this->assertEquals(3, $newResult['imported']['rows']);
    }

    public function testIncrementalFetchingByInteger(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = 'intColumn';
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertEquals(3, $result['state']['lastFetchedRow']);

        sleep(2);
        // the next fetch should be empty
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $noNewRowsResult = JsonHelper::decode($stdout);

        $this->assertEquals(1, $noNewRowsResult['imported']['rows']);

        sleep(2);
        //now add a couple rows and run it again.
        $this->pdo->exec(
            'INSERT INTO auto_increment_timestamp (`weird-Name`, `intColumn`)'
            . ' VALUES (\'charles\', 4), (\'william\', 7)'
        );

        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $newResult = JsonHelper::decode($stdout);

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $newResult);
        $this->assertArrayHasKey('lastFetchedRow', $newResult['state']);
        $this->assertEquals(7, $newResult['state']['lastFetchedRow']);
        $this->assertEquals(3, $newResult['imported']['rows']);
    }

    public function testIncrementalFetchingByDecimal(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = 'decimalColumn';
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertEquals(30.3, $result['state']['lastFetchedRow']);

        sleep(2);
        // the next fetch should be empty
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $noNewRowsResult = JsonHelper::decode($stdout);

        $this->assertEquals(1, $noNewRowsResult['imported']['rows']);

        sleep(2);
        //now add a couple rows and run it again.  Only the one row that has decimal >= to 30.3 should be included
        $this->pdo->exec(
            'INSERT INTO auto_increment_timestamp (`weird-Name`, `decimalColumn`)'
            . ' VALUES (\'charles\', 4.4), (\'william\', 70.7)'
        );

        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $newResult = JsonHelper::decode($stdout);

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $newResult);
        $this->assertArrayHasKey('lastFetchedRow', $newResult['state']);
        $this->assertEquals(70.7, $newResult['state']['lastFetchedRow']);
        $this->assertEquals(2, $newResult['imported']['rows']);
    }

    public function testIncrementalFetchingLimit(): void
    {
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingLimit'] = 1;
        $this->createAutoIncrementAndTimestampTable();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 1,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertEquals(1, $result['state']['lastFetchedRow']);

        sleep(2);
        // for the next fetch should contain the second row the limit must be 2 since we are using >=
        $config['parameters']['incrementalFetchingLimit'] = 2;
        $app = $this->createApplication($config, $result['state']);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);
        $this->assertEquals(
            [
                'outputTable' => 'in.c-main.auto-increment-timestamp',
                'rows' => 2,
            ],
            $result['imported']
        );

        //check that output state contains expected information
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('lastFetchedRow', $result['state']);
        $this->assertEquals(2, $result['state']['lastFetchedRow']);
    }

    public function testIncrementalOrdering(): void
    {
        $this->createAutoIncrementAndTimestampTable();
        $config = $this->getIncrementalFetchingConfig();

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = JsonHelper::decode($stdout);

        $outputCsvFile = new CsvReader($this->dataDir . '/out/tables/' . $result['imported']['outputTable'] . '.csv');
        $previousId = 0;
        foreach ($outputCsvFile as $key => $row) {
            $this->assertGreaterThan($previousId, (int) $row[0]);
            $previousId = (int) $row[0];
        }
    }

    /**
     * @dataProvider invalidColumnProvider
     */
    public function testIncrementalFetchingInvalidColumns(string $column, string $expectedExceptionMessage): void
    {
        $this->createAutoIncrementAndTimestampTable();
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['incrementalFetchingColumn'] = $column;

        $this->expectException(UserException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        ($this->createApplication($config))->run();
    }

    public function invalidColumnProvider(): array
    {
        return [
            'column does not exist' => [
                "fakeCol",
                "Column \"fakeCol\" specified for incremental fetching was not found in the table",
            ],
            'column exists but is not auto-increment nor updating timestamp so should fail' => [
                "weird-Name",
                "Column \"weird-Name\" specified for incremental fetching is not a numeric or timestamp type column",
            ],
        ];
    }

    public function testIncrementalFetchingInvalidConfig(): void
    {
        $this->createAutoIncrementAndTimestampTable();
        $config = $this->getIncrementalFetchingConfig();
        $config['parameters']['query'] = 'SELECT * FROM auto_increment_timestamp';
        unset($config['parameters']['table']);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "root.parameters":'
            . ' Incremental fetching is not supported for advanced queries.'
        );
        $this->createApplication($config);
    }
}
