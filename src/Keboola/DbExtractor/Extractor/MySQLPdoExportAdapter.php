<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\Csv\Exception as CsvException;
use Keboola\DbExtractor\Adapter\Exception\ApplicationException;
use Keboola\DbExtractor\Adapter\PDO\PdoExportAdapter;
use Keboola\DbExtractor\Adapter\ValueObject\ExportResult;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Table;
use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;

class MySQLPdoExportAdapter extends PdoExportAdapter
{
    public function export(ExportConfig $exportConfig, string $csvFilePath, ?Table $tableMetadata = null): ExportResult
    {
        $query = $exportConfig->hasQuery() ? $exportConfig->getQuery() : $this->createSimpleQuery($exportConfig);

        try {
            return $this->queryAndProcess(
                $query,
                $exportConfig->getMaxRetries(),
                function (QueryResult $result) use ($exportConfig, $csvFilePath, $tableMetadata) {
                    /** @var MySQLResultWriter $resultWriter */
                    $resultWriter = $this->resultWriter;
                    return $resultWriter->writeToCsv($result, $exportConfig, $csvFilePath, $tableMetadata);
                },
            );
        } catch (CsvException $e) {
            throw new ApplicationException('Failed writing CSV File: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (UserExceptionInterface $e) {
            throw $this->handleDbError($e, $exportConfig->getMaxRetries(), $exportConfig->getOutputTable());
        }
    }
}
