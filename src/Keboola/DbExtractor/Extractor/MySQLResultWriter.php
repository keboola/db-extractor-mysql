<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\ResultWriter\DefaultResultWriter;
use Keboola\DbExtractor\Adapter\ValueObject\ExportResult;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;
use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;

class MySQLResultWriter extends DefaultResultWriter
{
    protected function hasCsvHeader(ExportConfig $exportConfig): bool
    {
        return false;
    }

    public function writeToCsv(
        QueryResult $result,
        ExportConfig $exportConfig,
        string $csvFilePath,
    ): ExportResult {
        $this->rowsCount = 0;
        $this->lastRow = null;

        // Create CSV writer
        $csvWriter = $this->createCsvWriter($csvFilePath);

        // Get iterator
        $iterator = $this->getIterator($result);

        // Write rows
        try {
            $this->writeRows($iterator, $result->getMetadata(), $exportConfig, $csvWriter);
        } finally {
            $result->closeCursor();
        }

        $incFetchingColMaxValue = $this->lastRow ?
            $this->getIncrementalFetchingValueFromLastRow($exportConfig) :
            $this->getIncrementalFetchingValueFromState($exportConfig);

        return new ExportResult(
            $csvFilePath,
            $this->rowsCount,
            $result->getMetadata(),
            $this->hasCsvHeader($exportConfig),
            $incFetchingColMaxValue,
        );
    }
}
