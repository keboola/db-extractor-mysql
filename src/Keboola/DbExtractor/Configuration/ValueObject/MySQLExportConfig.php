<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration\ValueObject;

use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\IncrementalFetchingConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\InputTable;

class MySQLExportConfig extends ExportConfig
{
    private bool $convertBin2hex;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['name'] ?? null,
            $data['query'] ?? null,
            empty($data['query']) ? InputTable::fromArray($data) : null,
            $data['incremental'] ?? false,
            empty($data['query']) ? IncrementalFetchingConfig::fromArray($data) : null,
            $data['columns'],
            $data['outputTable'],
            $data['primaryKey'],
            $data['retries'],
            $data['convertBin2hex'],
        );
    }

    public function __construct(
        ?int $configId,
        ?string $configName,
        ?string $query,
        ?InputTable $table,
        bool $incrementalLoading,
        ?IncrementalFetchingConfig $incrementalFetchingConfig,
        array $columns,
        string $outputTable,
        array $primaryKey,
        int $maxRetries,
        bool $convertBin2hex,
    ) {
        parent::__construct(
            $configId,
            $configName,
            $query,
            $table,
            $incrementalLoading,
            $incrementalFetchingConfig,
            $columns,
            $outputTable,
            $primaryKey,
            $maxRetries,
        );

        $this->convertBin2hex = $convertBin2hex;
    }

    public function hasConvertBin2hex(): bool
    {
        return $this->convertBin2hex;
    }
}
