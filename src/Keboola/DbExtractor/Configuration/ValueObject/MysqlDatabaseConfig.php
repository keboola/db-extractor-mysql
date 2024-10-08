<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration\ValueObject;

use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\SSLConnectionConfig;
use Keboola\DbExtractorConfig\Exception\PropertyNotSetException;

class MysqlDatabaseConfig extends DatabaseConfig
{

    private ?string $transactionIsolationLevel;

    private bool $networkCompression;

    private ?int $queryTimeout;

    public static function fromArray(array $data): self
    {
        $sslEnabled = !empty($data['ssl']) && !empty($data['ssl']['enabled']);

        return new self(
            $data['host'],
            $data['port'] ? (string) $data['port'] : null,
            $data['user'],
            $data['#password'],
            $data['database'] ?? null,
            $data['schema'] ?? null,
            $sslEnabled ? SSLConnectionConfig::fromArray($data['ssl']) : null,
            $data['transactionIsolationLevel'] ?? null,
            $data['networkCompression'] ?? false,
            $data['initQueries'] ?? [],
            $data['queryTimeout'] ?? null,
        );
    }

    public function __construct(
        string $host,
        ?string $port,
        string $username,
        string $password,
        ?string $database,
        ?string $schema,
        ?SSLConnectionConfig $sslConnectionConfig,
        ?string $transactionIsolationLevel,
        bool $networkCompression,
        array $initQueries,
        ?int $queryTimeout,
    ) {
        $this->transactionIsolationLevel = $transactionIsolationLevel;
        $this->networkCompression = $networkCompression;
        $this->queryTimeout = $queryTimeout;

        parent::__construct($host, $port, $username, $password, $database, $schema, $sslConnectionConfig, $initQueries);
    }

    public function isNetworkCompressionEnabled(): bool
    {
        return $this->networkCompression;
    }

    public function hasTransactionIsolationLevel(): bool
    {
        return $this->transactionIsolationLevel !== null;
    }

    public function getTransactionIsolationLevel(): string
    {
        if ($this->transactionIsolationLevel === null) {
            throw new PropertyNotSetException('Property "transactionIsolationLevel" is not set.');
        }
        return $this->transactionIsolationLevel;
    }

    public function hasQueryTimeout(): bool
    {
        return $this->queryTimeout !== null;
    }

    public function getQueryTimeout(): int
    {
        if ($this->queryTimeout === null) {
            throw new PropertyNotSetException('Property "queryTimeout" is not set.');
        }
        return $this->queryTimeout;
    }
}
