<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

class PerformanceTest extends AbstractMySQLTest
{

    private function cleanupTestSchemas(int $numberOfSchemas, int $numberOfTablesPerSchema): void
    {
        // cleanup
        for ($schemaCount = 0; $schemaCount < $numberOfSchemas; $schemaCount++) {
            for ($tableCount = 0; $tableCount < $numberOfTablesPerSchema; $tableCount++) {
                $this->pdo->exec(sprintf(
                    'DROP TABLE IF EXISTS %s.%s',
                    sprintf("testschema_%d", $schemaCount),
                    sprintf("testtable_%d", $tableCount)
                ));
            }
            $this->pdo->exec(sprintf("DROP SCHEMA IF EXISTS `testschema_%d`", $schemaCount));
        }
    }

    public function testThousandsOfTablesGetTables(): void
    {
        // $this->markTestSkipped("No need to run this test every time.");
        $testStartTime = time();
        $numberOfSchemas = 10;
        $numberOfTablesPerSchema = 200;
        $numberOfColumnsPerTable = 50;
        $numberOfRowsPerTable = 100;
        $maxRunTime = 5;

        $this->cleanupTestSchemas($numberOfSchemas, $numberOfTablesPerSchema);

        // gen columns
        $columnsSql = "";
        $valuesSql = "";
        $columnsInsertSql = "";
        for ($columnCount = 0; $columnCount < $numberOfColumnsPerTable; $columnCount++) {
            $columnsSql .= sprintf(", `col_%d` VARCHAR(50) NOT NULL DEFAULT ''", $columnCount);
            $columnsInsertSql .= sprintf(
                "%s `col_%d`",
                ($columnCount > 0) ? "," : "",
                $columnCount
            );
            $valuesSql .= sprintf(
                "%s '%s'",
                ($columnCount > 0) ? "," : "",
                substr(str_shuffle(md5(microtime())), 0, 10)
            );
        }

        for ($schemaCount = 0; $schemaCount < $numberOfSchemas; $schemaCount++) {
            $this->pdo->exec(sprintf("CREATE SCHEMA `testschema_%d`", $schemaCount));
            for ($tableCount = 0; $tableCount < $numberOfTablesPerSchema; $tableCount++) {
                $this->pdo->exec(
                    sprintf(
                        "CREATE TABLE `testschema_%d`.`testtable_%d` (`ID` INT NOT NULL AUTO_INCREMENT%s, PRIMARY KEY (`ID`))",
                        $schemaCount,
                        $tableCount,
                        $columnsSql
                    )
                );
                $fullValuesSql = "(";
                for ($rowCount = 0; $rowCount < $numberOfRowsPerTable; $rowCount++) {
                    if ($rowCount < $numberOfRowsPerTable - 1) {
                        $fullValuesSql .= $valuesSql . "), (";
                    } else {
                        $fullValuesSql .= $valuesSql . ")";
                    }
                }
                $sql = sprintf(
                    "INSERT INTO `testschema_%d`.`testtable_%d` (%s) VALUES %s",
                    $schemaCount,
                    $tableCount,
                    $columnsInsertSql,
                    $fullValuesSql
                );
                $this->pdo->exec(
                    $sql
                );
            }
        }

        $dbBuildTime = time() - $testStartTime;
        echo "\nTest DB built in  " . $dbBuildTime . " seconds.\n";

        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $app = $this->createApplication($config);

        $jobStartTime = time();
        $result = $app->run();
        $this->assertEquals('success', $result['status']);
        $runTime = time() - $jobStartTime;

        $this->assertLessThan($maxRunTime, $runTime);

        echo "\nThe tables were fetched in " . $runTime . " seconds.\n";
        $this->cleanupTestSchemas($numberOfSchemas, $numberOfTablesPerSchema);
        $entireTime = time() - $testStartTime;
        echo "\nComplete test finished in  " . $entireTime . " seconds.\n";
    }
}
