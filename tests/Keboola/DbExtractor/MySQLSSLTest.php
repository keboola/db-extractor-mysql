<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\CommonExceptions\UserExceptionInterface;
use SplFileInfo;

class MySQLSSLTest extends AbstractMySQLTest
{
    public function testSSLEnabled(): void
    {
        $status = $this->pdo->query("SHOW STATUS LIKE 'Ssl_cipher';")->fetch(\PDO::FETCH_ASSOC);

        $this->assertArrayHasKey('Value', $status);
        $this->assertNotEmpty($status['Value']);
    }

    public function testCredentials(): void
    {
        $config = $this->getConfig();
        $config['action'] = 'testConnection';

        $config['parameters']['db']['ssl'] = [
            'enabled' => true,
            'ca' => file_get_contents('/ssl-cert/ca.pem'),
            'cert' => file_get_contents('/ssl-cert/client-cert.pem'),
            'key' => file_get_contents('/ssl-cert/client-key.pem'),
        ];

        $config['parameters']['tables'] = [];

        $app = $this->createApplication($config);
        $result = $app->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testRun(): void
    {
        $config = $this->getConfig();

        $config['parameters']['db']['ssl'] = [
            'enabled' => true,
            'ca' => file_get_contents('/ssl-cert/ca.pem'),
            'cert' => file_get_contents('/ssl-cert/client-cert.pem'),
            'key' => file_get_contents('/ssl-cert/client-key.pem'),
        ];

        $app = $this->createApplication($config);

        $csv1 = new SplFileInfo($this->dataDir . '/mysql/sales.csv');
        $this->createTextTable($csv1);

        $csv2 = new SplFileInfo($this->dataDir . '/mysql/escaping.csv');
        $this->createTextTable($csv2);

        $result = $app->run();

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $filename = $this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv.manifest';
        $this->assertFileExists($filename);
        $this->assertFileEquals((string) $csv1, $outputCsvFile);

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][1]['outputTable'] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $filename = $this->dataDir . '/out/tables/' . $result['imported'][1]['outputTable'] . '.csv.manifest';
        $this->assertFileExists($filename);
        $this->assertFileEquals((string) $csv2, $outputCsvFile);
    }

    public function testCipher(): void
    {
        $config = $this->getConfig();

        $config['parameters']['db']['ssl'] = [
            'enabled' => true,
            'cipher' => 'DES', // required ciphers are not enabled
            'ca' => file_get_contents('/ssl-cert/ca.pem'),
            'cert' => file_get_contents('/ssl-cert/client-cert.pem'),
            'key' => file_get_contents('/ssl-cert/client-key.pem'),
        ];

        $app = $this->createApplication($config);
        $this->createTextTable(new SplFileInfo($this->dataDir . '/mysql/sales.csv'));
        $this->createTextTable(new SplFileInfo($this->dataDir . '/mysql/escaping.csv'));

        $this->expectException(UserExceptionInterface::class);
        $this->expectExceptionMessage('Cannot connect to MySQL by using SSL');
        $app->run();
    }
}
