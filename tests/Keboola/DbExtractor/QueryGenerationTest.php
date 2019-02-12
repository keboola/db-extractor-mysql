<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Tests\Keboola\DbExtractor;

use Keboola\Component\Logger;
use Keboola\DbExtractorCommon\Configuration\TableDetailParameters;
use Keboola\MysqlExtractor\MysqlExtractor;

class QueryGenerationTest extends AbstractMySQLTest
{
    /** @var array */
    private $config;

    public function setUp(): void
    {
        $this->dataDir = __DIR__ . '/../../data';
        $this->config = $this->getConfigRow(self::DRIVER);
    }

    /**
     * @dataProvider simpleTableColumnsDataProvider
     */
    public function testGetSimplifiedPdoQuery(array $params, array $state, string $expected): void
    {
        $this->prepareConfigInDataDir($this->config);
        $this->prepareInputStateInDataDir($state);
        $extractor = new MysqlExtractor(new Logger());

        if (isset($params['incrementalFetchingColumn']) && $params['incrementalFetchingColumn'] !== "") {
            $extractor->validateIncrementalFetching(
                $params['table'],
                $params['incrementalFetchingColumn'],
                isset($params['incrementalFetchingLimit']) ? $params['incrementalFetchingLimit'] : null
            );
        }
        $query = $extractor->simpleQuery($params['table'], $params['columns']);
        $this->assertEquals($expected, $query);
    }

    public function simpleTableColumnsDataProvider(): array
    {
        return [
            // simple table select with all columns
            [
                [
                    'table' => new TableDetailParameters('testSchema', 'test'),
                    'columns' => [],
                ],
                [],
                "SELECT * FROM `testSchema`.`test`",
            ],
            // simple table select with all columns (columns as null)
            [
                [
                    'table' => new TableDetailParameters('testSchema', 'test'),
                    'columns' => [],
                ],
                [],
                "SELECT * FROM `testSchema`.`test`",
            ],
            // simple table with 2 columns selected
            [
                [
                    'table' => new TableDetailParameters('testSchema', 'test'),
                    'columns' => ["col1", "col2"],
                ],
                [],
                "SELECT `col1`, `col2` FROM `testSchema`.`test`",
            ],
            // test simplePDO query with limit and timestamp column but no state
            [
                [
                    'table' => new TableDetailParameters('test', 'auto_increment_timestamp'),
                    'columns' => [],
                    'incrementalFetchingLimit' => 10,
                    'incrementalFetchingColumn' => 'timestamp',
                ],
                [],
                "SELECT * FROM `test`.`auto_increment_timestamp` ORDER BY `timestamp` LIMIT 10",
            ],
            // test simplePDO query with limit and idp column and previos state
            [
                [
                    'table' => new TableDetailParameters('test', 'auto_increment_timestamp'),
                    'columns' => [],
                    'incrementalFetchingLimit' => 10,
                    'incrementalFetchingColumn' => '_weird-I-d',
                ],
                [
                    "lastFetchedRow" => 4,
                ],
                "SELECT * FROM `test`.`auto_increment_timestamp` WHERE `_weird-I-d` >= '4'"
                . " ORDER BY `_weird-I-d` LIMIT 10",
            ],
            // test simplePDO query timestamp column but no state and no limit
            [
                [
                    'table' => new TableDetailParameters('test', 'auto_increment_timestamp'),
                    'columns' => [],
                    'incrementalFetchingLimit' => null,
                    'incrementalFetchingColumn' => 'timestamp',
                ],
                [],
                "SELECT * FROM `test`.`auto_increment_timestamp` ORDER BY `timestamp`",
            ],
            // test simplePDO query id column and previos state and no limit
            [
                [
                    'table' => new TableDetailParameters('test', 'auto_increment_timestamp'),
                    'columns' => [],
                    'incrementalFetchingLimit' => 0,
                    'incrementalFetchingColumn' => '_weird-I-d',
                ],
                [
                    "lastFetchedRow" => 4,
                ],
                "SELECT * FROM `test`.`auto_increment_timestamp` WHERE `_weird-I-d` >= '4' ORDER BY `_weird-I-d`",
            ],
        ];
    }
}
