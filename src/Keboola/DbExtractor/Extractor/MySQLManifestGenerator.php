<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\ValueObject\ExportResult;
use Keboola\DbExtractor\Configuration\ValueObject\MySQLExportConfig;
use Keboola\DbExtractor\Manifest\DefaultManifestGenerator;
use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;

class MySQLManifestGenerator extends DefaultManifestGenerator
{
    public function generate(ExportConfig $exportConfig, ExportResult $exportResult, bool $legacy = false): array
    {
        // Opt-in only: existing typed tables were created with STRING columns for these types
        if ($exportConfig instanceof MySQLExportConfig
            && $exportConfig->hasQuery()
            && $exportConfig->hasQueryNativeTypes()
        ) {
            $exportResult = new ExportResult(
                $exportResult->getCsvPath(),
                $exportResult->getRowsCount(),
                new MySQLQueryMetadata($exportResult->getQueryMetadata()),
                $exportResult->hasCsvHeader(),
                $exportResult->getIncFetchingColMaxValue(),
            );
        }

        return parent::generate($exportConfig, $exportResult, $legacy);
    }
}
