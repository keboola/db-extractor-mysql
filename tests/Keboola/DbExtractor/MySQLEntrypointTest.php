<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\Csv\CsvFile;
use Symfony\Component\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class MySQLEntrypointTest extends AbstractMySQLTest
{
    /** @var string */
    protected $rootPath = __DIR__ . '/../../..';

    /**
     * @dataProvider configTypesProvider
     */
    public function testRunAction(string $configType): void
    {
        $outputCsvFile = $this->dataDir . '/out/tables/in.c-main.sales.csv';
        $outputCsvFile2 = $this->dataDir . '/out/tables/in.c-main.escaping.csv';

        @unlink($outputCsvFile);
        @unlink($outputCsvFile2);

        @unlink($this->dataDir . '/config.json');
        @unlink($this->dataDir . '/config.yml');

        $config = $this->getConfig(self::DRIVER, $configType);
        if ($configType === 'json') {
            file_put_contents($this->dataDir . '/config.json', json_encode($config));
        } else {
            file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));
        }

        $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');
        $this->createTextTable($csv1);

        $csv2 = new CsvFile($this->dataDir . '/mysql/escaping.csv');
        $this->createTextTable($csv2);

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        echo $process->getErrorOutput();
        echo $process->getOutput();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/in.c-main.sales.csv.manifest');
        $this->assertFileEquals((string) $csv1, $outputCsvFile);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/in.c-main.escaping.csv.manifest');
        $this->assertFileEquals((string) $csv2, $outputCsvFile2);
    }

    public function testRunActionSshTunnel(): void
    {

        @unlink($this->dataDir . '/config.json');
        @unlink($this->dataDir . '/config.yml');
        $config = $this->getConfig(self::DRIVER);
        $config['parameters']['db']['ssh'] = [
            'enabled' => true,
            'keys' => [
                '#private' => $this->getPrivateKey(),
                'public' => $this->getPublicKey(),
            ],
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'remoteHost' => self::DRIVER,
            'remotePort' => $this->getEnv('mysql', 'DB_PORT'),
            'localPort' => '1234',
        ];
        file_put_contents($this->dataDir . '/config.json', json_encode($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertStringContainsString('Creating SSH tunnel to \'sshproxy\' on local port \'1234\'', $process->getOutput());
        $this->assertStringContainsString('host=127.0.0.1;port=1234;dbname=test;', $process->getOutput());
    }

    public function testTestConnectionAction(): void
    {
        $config = $this->getConfig();
        @unlink($this->dataDir . '/config.yml');
        $config['action'] = 'testConnection';
        file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();
        $this->assertJson($process->getOutput());
        $this->assertEquals(0, $process->getExitCode());
        $this->assertEquals('', $process->getErrorOutput());
    }

    public function testTestConnectionActionWithSSH(): void
    {
        $config = $this->getConfig();
        @unlink($this->dataDir . '/config.yml');
        $config['action'] = 'testConnection';
        $config['parameters']['db']['ssh'] = [
            'enabled' => true,
            'keys' => [
                '#private' => $this->getPrivateKey(),
                'public' => $this->getPublicKey(),
            ],
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'remoteHost' => 'mysql',
            'remotePort' => $this->getEnv('mysql', 'DB_PORT'),
            'localPort' => '15211',
        ];
        file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();
        $this->assertJson($process->getOutput());
        $this->assertEquals(0, $process->getExitCode());
        $this->assertEquals('', $process->getErrorOutput());
    }

    public function testGetTablesAction(): void
    {
        $config = $this->getConfig();
        $config['action'] = 'getTables';
        @unlink($this->dataDir . '/config.yml');
        file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertJson($process->getOutput());
        $this->assertEquals('', $process->getErrorOutput());
    }

    public function testTableColumnsQuery(): void
    {
        $outputCsvFile = $this->dataDir . '/out/tables/in.c-main.tablecolumns.csv';

        @unlink($outputCsvFile);

        $config = $this->getConfig();
        unset($config['parameters']['tables'][0]);
        unset($config['parameters']['tables'][1]);
        @unlink($this->dataDir . '/config.yml');
        file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));

        $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');
        $this->createTextTable($csv1);

        $expectedOutput = new CsvFile($this->dataDir . '/mysql/tableColumns.csv');

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/in.c-main.tablecolumns.csv.manifest');
        $this->assertFileEquals((string) $expectedOutput, $outputCsvFile);
        $this->assertFileExists($outputCsvFile);
    }

    public function testRetries(): void
    {
        $outputCsvFile = $this->dataDir . '/out/tables/in.c-main.tablecolumns.csv';

        @unlink($outputCsvFile);

        $config = $this->getConfig();
        $table = $config['parameters']['tables'][2]['table'];
        unset($config['parameters']['tables'][0]);
        unset($config['parameters']['tables'][1]);

        @unlink($this->dataDir . '/config.yml');
        file_put_contents($this->dataDir . '/config.yml', Yaml::dump($config));

        // try exporting before the table exists

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->start();

        // Drop the table if it exists
        $this->pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`.`%s`', $table['schema'], $table['tableName']));

        $tableCreated = false;
        while ($process->isRunning()) {
            sleep(5);
            if (!$tableCreated) {
                $csv1 = new CsvFile($this->dataDir . '/mysql/sales.csv');
                $this->createTextTable($csv1, $table['tableName']);
                $tableCreated = true;
            }
        }

        // check that it had to retry at least 2x
        $this->assertStringContainsString('[2x]', $process->getOutput());

        $expectedOutput = new CsvFile($this->dataDir . '/mysql/tableColumns.csv');

        $this->assertEquals(0, $process->getExitCode());
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/in.c-main.tablecolumns.csv.manifest');
        $this->assertFileEquals((string) $expectedOutput, $outputCsvFile);
        $this->assertFileExists($outputCsvFile);
    }

    public function testRunConfigRow(): void
    {
        $outputCsvFile = $this->dataDir . '/out/tables/in.c-main.escaping.csv';
        @unlink($outputCsvFile);

        $outputStateFile = $this->dataDir . '/out/state.json';
        // unset the state file
        @unlink($outputStateFile);

        $config = $this->getConfigRow(self::DRIVER);

        @unlink($this->dataDir . '/config.yml');
        @unlink($this->dataDir . '/config.json');

        file_put_contents($this->dataDir . '/config.json', json_encode($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $expectedOutput = new CsvFile($this->dataDir . '/mysql/escaping.csv');

        $this->assertEquals(0, $process->getExitCode());
        // state file should not be written if state is empty
        $this->assertFileNotExists($outputStateFile);
        $this->assertFileExists($outputCsvFile);
        $this->assertFileExists($this->dataDir . '/out/tables/in.c-main.escaping.csv.manifest');
        $this->assertFileEquals((string) $expectedOutput, $outputCsvFile);
        $this->assertFileExists($outputCsvFile);
    }

    public function testRunIncrementalFetching(): void
    {
        $this->createAutoIncrementAndTimestampTable();
        $config = $this->getConfigRow(self::DRIVER);

        @unlink($this->dataDir . '/config.yml');
        @unlink($this->dataDir . '/config.json');

        $inputStateFile = $this->dataDir . '/in/state.json';

        $fs = new Filesystem\Filesystem();
        if (!$fs->exists($inputStateFile)) {
            $fs->mkdir($this->dataDir . '/in');
            $fs->touch($inputStateFile);
        }
        $outputStateFile = $this->dataDir . '/out/state.json';
        // unset the state file
        @unlink($outputStateFile);
        @unlink($inputStateFile);

        unset($config['parameters']['query']);
        $config['parameters']['table'] = [
            'tableName' => 'auto_increment_timestamp',
            'schema' => 'test',
        ];
        $config['parameters']['incremental'] = true;
        $config['parameters']['name'] = 'auto-increment-timestamp';
        $config['parameters']['outputTable'] = 'in.c-main.auto-increment-timestamp';
        $config['parameters']['primaryKey'] = ['_weird-I-d'];
        $config['parameters']['incrementalFetchingColumn'] = '_weird-I-d';

        file_put_contents($this->dataDir . '/config.json', json_encode($config));

        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertFileExists($outputStateFile);
        $this->assertEquals(['lastFetchedRow' => '2'], json_decode((string) file_get_contents($outputStateFile), true));

        // add a couple rows
        $this->pdo->exec('INSERT INTO auto_increment_timestamp (`weird-Name`) VALUES (\'charles\'), (\'william\')');

        // copy state to input state file
        file_put_contents($inputStateFile, file_get_contents($outputStateFile));

        // run the config again
        $process = Process::fromShellCommandline('php ' . $this->rootPath . '/src/run.php --data=' . $this->dataDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $this->assertEquals(['lastFetchedRow' => '4'], json_decode((string) file_get_contents($outputStateFile), true));
    }
}
