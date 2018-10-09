<?php

declare(strict_types=1);

namespace Keboola\ExMySql\Tests\Keboola\DbExtractor;

use Keboola\Csv\CsvReader;

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
            'ca' => file_get_contents($this->dataDir . '/mysql/ssl/ca.pem'),
            'cert' => file_get_contents($this->dataDir . '/mysql/ssl/client-cert.pem'),
            'key' => file_get_contents($this->dataDir . '/mysql/ssl/client-key.pem'),
        ];

        $config['parameters']['tables'] = [];

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = json_decode($stdout, true);

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }

    public function testRun(): void
    {
        $config = $this->getConfig();

        $config['parameters']['db']['ssl'] = [
            'enabled' => true,
            'ca' => file_get_contents($this->dataDir . '/mysql/ssl/ca.pem'),
            'cert' => file_get_contents($this->dataDir . '/mysql/ssl/client-cert.pem'),
            'key' => file_get_contents($this->dataDir . '/mysql/ssl/client-key.pem'),
        ];

        $csv1FilePath = $this->dataDir . '/mysql/sales.csv';
        $csv1 = new CsvReader($csv1FilePath);
        $this->createTextTable($csv1, $csv1FilePath);

        $csv2FilePath = $this->dataDir . '/mysql/escaping.csv';
        $csv2 = new CsvReader($csv2FilePath);
        $this->createTextTable($csv2, $csv2FilePath);

        $app = $this->createApplication($config);
        $stdout = $this->runApplication($app);
        $result = json_decode($stdout, true);

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $result['imported'][0]['outputTable'] . '.csv.manifest');
        $this->assertFileEquals($csv1FilePath, $outputCsvFile);

        $outputCsvFile = $this->dataDir . '/out/tables/' . $result['imported'][1]['outputTable'] . '.csv';

        $this->assertEquals('success', $result['status']);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/' . $result['imported'][1]['outputTable'] . '.csv.manifest');
        $this->assertFileEquals($csv2FilePath, $outputCsvFile);
    }
}
