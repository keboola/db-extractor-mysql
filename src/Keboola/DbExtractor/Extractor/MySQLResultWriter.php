<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Iterator;
use Keboola\Csv\CsvWriter;
use Keboola\DbExtractor\Adapter\ResultWriter\DefaultResultWriter;
use Keboola\DbExtractor\Adapter\ValueObject\ExportResult;
use Keboola\DbExtractor\Adapter\ValueObject\QueryMetadata;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;
use Keboola\DbExtractor\Configuration\ValueObject\MySQLExportConfig;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Table;
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
        ?Table $tableMetadata = null,
    ): ExportResult {
        $this->rowsCount = 0;
        $this->lastRow = null;

        // Create CSV writer
        $csvWriter = $this->createCsvWriter($csvFilePath);

        // Get iterator
        $iterator = $this->getIterator($result);

        // Write rows
        try {
            $this->writeRows($iterator, $result->getMetadata(), $exportConfig, $csvWriter, $tableMetadata);
        } finally {
            $result->closeCursor();
        }

        /** @var string|null $incFetchingColMaxValue */
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

    protected function writeRows(
        Iterator $iterator,
        QueryMetadata $queryMetadata,
        MySQLExportConfig|ExportConfig $exportConfig,
        CsvWriter $csvWriter,
        ?Table $tableMetadata = null,
    ): void {
        // Write header
        if ($this->hasCsvHeader($exportConfig)) {
            $this->writeHeader($queryMetadata->getColumns()->getNames(), $csvWriter);
        }

        $columnsTypes = [];
        if ($tableMetadata !== null) {
            foreach ($tableMetadata->getColumns() as $column) {
                $columnsTypes[$column->getName()] = $column->getType();
            }
        }

        // Write the rest
        $this->rowsCount = 0;
        $this->lastRow = null;
        while ($iterator->valid()) {
            /** @var array<string, string> $resultRow */
            $resultRow = $iterator->current();
            if ($exportConfig instanceof MySQLExportConfig && $exportConfig->hasConvertBin2hex() === true) {
                $resultRow = $this->convertBinColumnsToHex($resultRow, $columnsTypes);
            }
            $this->writeRow($resultRow, $csvWriter);
            $iterator->next();

            $this->lastRow = $resultRow;
            $this->rowsCount++;
        }
    }

    /**
     * @param array<string, string> $resultRow
     * @param array<string, string> $columnsTypes
     * @return array<string, string>
     */
    private function convertBinColumnsToHex(array $resultRow, array $columnsTypes): array
    {
        foreach ($resultRow as $key => $value) {
            if (array_key_exists($key, $columnsTypes) === true && $columnsTypes[$key] === 'binary') {
                // Skip null values, keep them as null
                if ($value !== null) {
                    $resultRow[$key] = bin2hex($value);
                }
            }
        }
        return $resultRow;
    }
}
