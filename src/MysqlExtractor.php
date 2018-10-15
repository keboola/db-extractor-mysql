<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor;

use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;
use Keboola\DbExtractorCommon\BaseExtractor;
use Keboola\DbExtractorCommon\Configuration\BaseExtractorConfig;
use Keboola\DbExtractorCommon\Configuration\TableDetailParameters;
use Keboola\DbExtractorCommon\Configuration\TableParameters;
use Keboola\DbExtractorCommon\Exception\ApplicationException;
use Keboola\DbExtractorCommon\Exception\DeadConnectionException;
use Keboola\DbExtractorCommon\RetryProxy;
use Keboola\MysqlExtractor\Configuration\Config;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigActionDefinition;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigDefinition;
use Keboola\MysqlExtractor\Configuration\Definition\MySQLConfigRowDefinition;
use Keboola\Temp\Temp;

class MysqlExtractor extends BaseExtractor
{
    public const COLUMN_TYPE_AUTO_INCREMENT = 'autoIncrement';
    public const COLUMN_TYPE_TIMESTAMP = 'timestamp';

    /**
     * @var \PDO|null
     */
    private $db;

    /** @var array|null */
    private $incrementalFetching;

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinition(string $action, bool $isConfigRow): string
    {
        if ($action !== 'run') {
            return MySQLConfigActionDefinition::class;
        } elseif ($isConfigRow) {
            return MySQLConfigRowDefinition::class;
        } else {
            return MySQLConfigDefinition::class;
        }
    }

    public function extract(BaseExtractorConfig $config): array
    {
        $imported = [];
        $outputState = [];
        if ($config->isConfigRow()) {
            $tableParameters = TableParameters::fromRaw($config->getParameters());
            $exportResults = $this->extractTable($tableParameters);
            if (isset($exportResults['state'])) {
                $outputState = $exportResults['state'];
                unset($exportResults['state']);
            }
            $imported = $exportResults;
        } else {
            foreach ($config->getEnabledTables() as $table) {
                $exportResults = $this->extractTable($table);
                $imported[] = $exportResults;
            }
        }

        return [
            'status' => 'success',
            'imported' => $imported,
            'state' => $outputState,
        ];
    }

    private function extractTable(TableParameters $table): array
    {
        /** @var Config $config */
        $config = $this->getConfig();
        if ($table->getTableDetail()
            && $config->getDbParameters()->getDatabase() !== $table->getTableDetail()->getSchema()
        ) {
            throw new UserException(sprintf(
                'Invalid Configuration in "%s".  The table schema "%s" is different from the connection database "%s"',
                $table->getOutputTable(),
                $table->getTableDetail()->getSchema(),
                $config->getDbParameters()->getDatabase()
            ));
        }

        $outputTable = $table->getOutputTable();

        $this->getLogger()->info("Exporting to " . $outputTable);

        $isAdvancedQuery = true;
        if ($table->getTableDetail() && !$table->getQuery()) {
            $isAdvancedQuery = false;
            $query = $this->simpleQuery($table->getTableDetail(), $table->getColumns());
        } else {
            $query = $table->getQuery();
        }

        $maxTries = $table->getRetries() > 1 ? $table->getRetries() : self::DEFAULT_MAX_TRIES;

        // this will retry on CsvException
        $proxy = new RetryProxy(
            $this->getLogger(),
            $maxTries,
            RetryProxy::DEFAULT_BACKOFF_INTERVAL,
            [\PDOException::class, DeadConnectionException::class, \ErrorException::class]
        );
        try {
            $result = $proxy->call(function () use ($query, $outputTable, $isAdvancedQuery) {
                $stmt = $this->executeQuery($query);
                $csvWriter = $this->createOutputCsv($outputTable);
                $result = $this->writeToCsv($stmt, $csvWriter, $isAdvancedQuery);
                $this->isAlive();
                return $result;
            });
        } catch (\Keboola\Csv\Exception $e) {
            throw new ApplicationException("Failed writing CSV File: " . $e->getMessage(), $e->getCode(), $e);
        } catch (\PDOException $e) {
            throw $this->handleDbError($e, $table, $maxTries);
        } catch (\ErrorException $e) {
            throw $this->handleDbError($e, $table, $maxTries);
        } catch (DeadConnectionException $e) {
            throw $this->handleDbError($e, $table, $maxTries);
        }

        if ($result['rows'] > 0) {
            $this->createManifest($table);
        } else {
            unlink($this->getOutputFilename($outputTable));
            $this->getLogger()->warning(
                sprintf(
                    'Query returned empty result. Nothing was imported to "%s"',
                    $table->getOutputTable()
                )
            );
        }

        $output = [
            'outputTable' => $outputTable,
            'rows' => $result['rows'],
        ];
        // output state
        if (!empty($result['lastFetchedRow'])) {
            $output['state']['lastFetchedRow'] = $result['lastFetchedRow'];
        }
        return $output;
    }

    private function executeQuery(string $query): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getTables(array $tables = []): array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES as c";

        $whereClause = " WHERE c.TABLE_SCHEMA != 'performance_schema' 
                          AND c.TABLE_SCHEMA != 'mysql'
                          AND c.TABLE_SCHEMA != 'information_schema'
                          AND c.TABLE_SCHEMA != 'sys'";

        if ($config->getDbParameters()->getDatabase()) {
            $whereClause = sprintf(
                " WHERE c.TABLE_SCHEMA = %s",
                $this->getConnection()->quote($config->getDbParameters()->getDatabase())
            );
        }

        if (count($tables) > 0) {
            $whereClause .= sprintf(
                " AND c.TABLE_NAME IN (%s) AND c.TABLE_SCHEMA IN (%s)",
                implode(',', array_map(function ($table) {
                    return $this->getConnection()->quote($table->getTableName());
                }, $tables)),
                implode(',', array_map(function ($table) {
                    return $this->getConnection()->quote($table->getSchema());
                }, $tables))
            );
        }

        $sql .= $whereClause;

        /** @var \PDOStatement $res */
        $res = $this->getConnection()->query($sql);
        /** @var array $arr */
        $arr = $res->fetchAll(\PDO::FETCH_ASSOC);
        if (count($arr) === 0) {
            return [];
        }

        $tableDefs = [];
        foreach ($arr as $table) {
            $curTable = $table['TABLE_SCHEMA'] . '.' . $table['TABLE_NAME'];
            $tableDefs[$curTable] = [
                'name' => $table['TABLE_NAME'],
                'schema' => (isset($table['TABLE_SCHEMA'])) ? $table['TABLE_SCHEMA'] : '',
                'type' => (isset($table['TABLE_TYPE'])) ? $table['TABLE_TYPE'] : '',
                'rowCount' => (isset($table['TABLE_ROWS'])) ? $table['TABLE_ROWS'] : '',
            ];
            if ($table["TABLE_COMMENT"]) {
                $tableDefs[$curTable]['description'] = $table['TABLE_COMMENT'];
            }
            if ($table["AUTO_INCREMENT"]) {
                $tableDefs[$curTable]['autoIncrement'] = $table['AUTO_INCREMENT'];
            }
        }

        ksort($tableDefs);

        $sql = "SELECT c.* FROM INFORMATION_SCHEMA.COLUMNS as c";
        $sql .= $whereClause;

        /** @var \PDOStatement $res */
        $res = $this->db->query($sql);
        /** @var array $rows */
        $rows = $res->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $i => $column) {
            $curTable = $column['TABLE_SCHEMA'] . '.' . $column['TABLE_NAME'];
            $length = ($column['CHARACTER_MAXIMUM_LENGTH']) ? $column['CHARACTER_MAXIMUM_LENGTH'] : null;
            if (is_null($length) && !is_null($column['NUMERIC_PRECISION'])) {
                if ($column['NUMERIC_SCALE'] > 0) {
                    $length = $column['NUMERIC_PRECISION'] . "," . $column['NUMERIC_SCALE'];
                } else {
                    $length = $column['NUMERIC_PRECISION'];
                }
            }
            $curColumn = [
                "name" => $column['COLUMN_NAME'],
                "sanitizedName" => \Keboola\Utils\sanitizeColumnName($column['COLUMN_NAME']),
                "type" => $column['DATA_TYPE'],
                "primaryKey" => ($column['COLUMN_KEY'] === "PRI") ? true : false,
                "length" => $length,
                "nullable" => ($column['IS_NULLABLE'] === "NO") ? false : true,
                "default" => $column['COLUMN_DEFAULT'],
                "ordinalPosition" => $column['ORDINAL_POSITION'],
            ];

            if ($column['COLUMN_COMMENT']) {
                $curColumn['description'] = $column['COLUMN_COMMENT'];
            }

            if ($column['EXTRA']) {
                $curColumn["extra"] = $column["EXTRA"];
                if ($column['EXTRA'] === 'auto_increment' && isset($tableDefs[$curTable]['autoIncrement'])) {
                    $curColumn['autoIncrement'] = $tableDefs[$curTable]['autoIncrement'];
                }
                if ($column['EXTRA'] === 'on update CURRENT_TIMESTAMP'
                    && $column['COLUMN_DEFAULT'] === 'CURRENT_TIMESTAMP'
                ) {
                    $tableDefs[$curTable]['timestampUpdateColumn'] = $column['COLUMN_NAME'];
                }
            }
            $tableDefs[$curTable]['columns'][$column['ORDINAL_POSITION'] - 1] = $curColumn;
            ksort($tableDefs[$curTable]['columns']);
        }

        // add additional info
        if (count($tables) > 0) {
            $additionalSql = "SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, 
                    CONSTRAINT_NAME, REFERENCED_TABLE_NAME, LOWER(REFERENCED_COLUMN_NAME) as REFERENCED_COLUMN_NAME, 
                    REFERENCED_TABLE_SCHEMA FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS c ";

            /** @var \PDOStatement $res */
            $res = $this->db->query($additionalSql . $whereClause);
            /** @var array $rows */
            $rows = $res->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $column) {
                $curColumn = [];
                if (array_key_exists('CONSTRAINT_NAME', $column) && !is_null($column['CONSTRAINT_NAME'])) {
                    $curColumn['constraintName'] = $column['CONSTRAINT_NAME'];
                }
                if (array_key_exists('REFERENCED_TABLE_NAME', $column) && !is_null($column['REFERENCED_TABLE_NAME'])) {
                    $curColumn['foreignKeyRefSchema'] = $column['REFERENCED_TABLE_SCHEMA'];
                    $curColumn['foreignKeyRefTable'] = $column['REFERENCED_TABLE_NAME'];
                    $curColumn['foreignKeyRefColumn'] = $column['REFERENCED_COLUMN_NAME'];
                }
                if (count($curColumn) > 0) {
                    $curTableName = $column['TABLE_SCHEMA'] . '.' . $column['TABLE_NAME'];
                    $filteredColumns = [];
                    if (isset($tableDefs[$curTableName]['columns'])) {
                        $filteredColumns = array_filter(
                            $tableDefs[$curTableName]['columns'],
                            function ($existingCol) use ($column) {
                                return $existingCol['name'] === $column['COLUMN_NAME'];
                            }
                        );
                    }
                    if (count($filteredColumns) === 0) {
                        throw new ApplicationException(
                            sprintf(
                                'This should never happen: Could not find reference column "%s" in table definition',
                                $column['COLUMN_NAME']
                            )
                        );
                    }
                    $existingColumnKey = array_keys($filteredColumns)[0];
                    foreach ($curColumn as $key => $value) {
                        $tableDefs[$curTableName]['columns'][$existingColumnKey][$key] = $value;
                    }
                }
            }
        }
        return array_values($tableDefs);
    }

    public function testConnection(): void
    {
        $db = $this->getConnection();
        /** @var \PDOStatement $stmt */
        $stmt = $db->query('SELECT NOW();');
        $stmt->execute();
    }

    public function createConnection(Config $config): \PDO
    {
        $databaseParameters = $config->getDbParameters();
        $isSsl = false;

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // convert errors to PDOExceptions
            \PDO::MYSQL_ATTR_COMPRESS => $config->isNetworkCompression(), // network compression
        ];

        // ssl encryption
        $sslParameters = $config->getSslParameters();
        if ($sslParameters && $sslParameters->isEnabled()) {
            $temp = new Temp(getenv('APP_NAME') ? getenv('APP_NAME') : 'ex-db-mysql');

            if ($sslParameters->getKey()) {
                $options[\PDO::MYSQL_ATTR_SSL_KEY] = $this->createSSLFile($sslParameters->getKey(), $temp);
                $isSsl = true;
            }
            if ($sslParameters->getCert()) {
                $options[\PDO::MYSQL_ATTR_SSL_CERT] = $this->createSSLFile($sslParameters->getCert(), $temp);
                $isSsl = true;
            }
            if ($sslParameters->getCa()) {
                $options[\PDO::MYSQL_ATTR_SSL_CA] = $this->createSSLFile($sslParameters->getCa(), $temp);
                $isSsl = true;
            }
            if ($sslParameters->getCipher()) {
                $options[\PDO::MYSQL_ATTR_SSL_CIPHER] = $sslParameters->getCipher();
            }
            if ($sslParameters->getVerifyServerCert() === false) {
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        $port = $databaseParameters->getPort() ?? '3306';

        if ($databaseParameters->getDatabase()) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8",
                $databaseParameters->getHost(),
                $port,
                $databaseParameters->getDatabase()
            );
        } else {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;charset=utf8",
                $databaseParameters->getHost(),
                $port
            );
        }

        $this->getLogger()->info("Connecting to DSN '" . $dsn . "' " . ($isSsl ? 'Using SSL' : ''));

        try {
            $pdo = new \PDO($dsn, $databaseParameters->getUser(), $databaseParameters->getPassword(), $options);
        } catch (\PDOException $e) {
            $checkCnMismatch = function (\Throwable $exception): void {
                if (strpos($exception->getMessage(), 'did not match expected CN') !== false) {
                    throw new UserException($exception->getMessage());
                }
            };
            $checkCnMismatch($e);
            $previous = $e->getPrevious();
            if ($previous !== null) {
                $checkCnMismatch($previous);
            }
            throw $e;
        }
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $pdo->exec("SET NAMES utf8;");

        if ($isSsl) {
            /** @var \PDOStatement $stmt */
            $stmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher';");
            $status = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (empty($status['Value'])) {
                throw new UserException(sprintf("Connection is not encrypted"));
            } else {
                $this->getLogger()->info("Using SSL cipher: " . $status['Value']);
            }
        }

        if ($config->isNetworkCompression()) {
            /** @var \PDOStatement $stmt */
            $stmt = $pdo->query("SHOW SESSION STATUS LIKE 'Compression';");
            $status = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (empty($status['Value']) || $status['Value'] !== 'ON') {
                throw new UserException(sprintf("Network communication is not compressed"));
            } else {
                $this->getLogger()->info("Using network communication compression");
            }
        }

        return $pdo;
    }

    private function createSSLFile(string $sslCa, Temp $temp): string
    {
        $filename = $temp->createTmpFile('ssl');
        file_put_contents((string) $filename, $sslCa);
        return (string) realpath((string) $filename);
    }

    public function getConnection(): \PDO
    {
        if (!$this->db) {
            /** @var Config $config */
            $config = $this->getConfig();

            $this->db = $this->createConnection($config);
        }
        return $this->db;
    }

    public function validateIncrementalFetching(
        TableDetailParameters $tableDetailParameters,
        string $columnName,
        ?int $limit = null
    ): void {
        $db = $this->getConnection();
        /** @var \PDOStatement $res */
        $res = $db->query(
            sprintf(
                'SELECT * FROM INFORMATION_SCHEMA.COLUMNS as cols 
                            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
                $db->quote($tableDetailParameters->getSchema()),
                $db->quote($tableDetailParameters->getTableName()),
                $db->quote($columnName)
            )
        );
        /** @var array $columns */
        $columns = $res->fetchAll();
        if (count($columns) === 0) {
            throw new UserException(
                sprintf(
                    'Column "%s" specified for incremental fetching was not found in the table',
                    $columnName
                )
            );
        }
        if ($columns[0]['EXTRA'] === 'auto_increment') {
            $this->incrementalFetching['column'] = $columnName;
            $this->incrementalFetching['type'] = self::COLUMN_TYPE_AUTO_INCREMENT;
        } else if ($columns[0]['DATA_TYPE'] === 'timestamp') {
            $this->incrementalFetching['column'] = $columnName;
            $this->incrementalFetching['type'] = self::COLUMN_TYPE_TIMESTAMP;
        } else {
            throw new UserException(
                sprintf(
                    'Column "%s" specified for incremental fetching is not'
                    . ' an auto increment column or an auto update timestamp',
                    $columnName
                )
            );
        }
        if ($limit) {
            $this->incrementalFetching['limit'] = $limit;
        }
    }

    private function writeToCsv(\PDOStatement $stmt, CsvWriter $csvWriter, bool $includeHeader = true): array
    {
        $output = [];

        $resultRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (is_array($resultRow) && !empty($resultRow)) {
            // write header and first line
            if ($includeHeader) {
                $csvWriter->writeRow(array_keys($resultRow));
            }
            $csvWriter->writeRow($resultRow);

            // write the rest
            $numRows = 1;
            $lastRow = $resultRow;

            while ($resultRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $csvWriter->writeRow($resultRow);
                $lastRow = $resultRow;
                $numRows++;
            }
            $stmt->closeCursor();

            if (isset($this->incrementalFetching['column'])) {
                if (!array_key_exists($this->incrementalFetching['column'], $lastRow)) {
                    throw new UserException(
                        sprintf(
                            'The specified incremental fetching column "%s" not found in the table. Available columns "%s".',
                            $this->incrementalFetching['column'],
                            implode(', ', array_keys($resultRow))
                        )
                    );
                }
                $output['lastFetchedRow'] = $lastRow[$this->incrementalFetching['column']];
            }
            $output['rows'] = $numRows;
            return $output;
        }
        // no rows found.  If incremental fetching is turned on, we need to preserve the last state
        if ($this->incrementalFetching['column'] && isset($this->state['lastFetchedRow'])) {
            $output = $this->state;
        }
        $output['rows'] = 0;
        return $output;
    }

    private function simpleQuery(TableDetailParameters $table, ?array $columns = array()): string
    {
        if (count($columns) > 0) {
            $columnQuery = implode(
                ', ',
                array_map(
                    function ($column) {
                        return $this->quote($column);
                    },
                    $columns
                )
            );
        } else {
            $columnQuery = '*';
        }

        $query = sprintf(
            "SELECT %s FROM %s.%s",
            $columnQuery,
            $this->quote($table->getSchema()),
            $this->quote($table->getTableName())
        );

        $incrementalAddon = null;
        if ($this->incrementalFetching && isset($this->state['lastFetchedRow'])) {
            if ($this->incrementalFetching['type'] === self::COLUMN_TYPE_AUTO_INCREMENT) {
                $incrementalAddon = sprintf(
                    ' %s > %d',
                    $this->quote($this->incrementalFetching['column']),
                    (int) $this->state['lastFetchedRow']
                );
            } else if ($this->incrementalFetching['type'] === self::COLUMN_TYPE_TIMESTAMP) {
                $incrementalAddon = sprintf(
                    " %s > '%s'",
                    $this->quote($this->incrementalFetching['column']),
                    $this->state['lastFetchedRow']
                );
            } else {
                throw new ApplicationException(
                    sprintf('Unknown incremental fetching column type "%s"', $this->incrementalFetching['type'])
                );
            }
        }

        if ($incrementalAddon) {
            $query .= sprintf(
                " WHERE %s ORDER BY %s",
                $incrementalAddon,
                $this->quote($this->incrementalFetching['column'])
            );
        }
        if (isset($this->incrementalFetching['limit'])) {
            $query .= sprintf(
                " LIMIT %d",
                $this->incrementalFetching['limit']
            );
        }
        return $query;
    }

    protected function handleDbError(\Throwable $e, ?TableParameters $table = null, ?int $counter = null): UserException
    {
        $message = "";
        if ($table) {
            $message = sprintf("[%s]: ", $table->getOutputTable());
        }
        $message .= sprintf('DB query failed: %s', $e->getMessage());
        if ($counter) {
            $message .= sprintf(' Tried %d times.', $counter);
        }
        return new UserException($message, 0, $e);
    }
}
