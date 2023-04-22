<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\Csv\Exception as CsvException;
use Keboola\DbExtractor\Adapter\BaseExportAdapter;
use Keboola\DbExtractor\Adapter\Exception\ApplicationException;
use Keboola\DbExtractor\Adapter\Query\QueryFactory;
use Keboola\DbExtractor\Adapter\ResultWriter\ResultWriter;
use Keboola\DbExtractor\Adapter\PDO\PdoConnection;
use Keboola\DbExtractor\Adapter\ValueObject\ExportResult;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;
use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;
use Psr\Log\LoggerInterface;

class PdoExportVerboseLogAdapter extends BaseExportAdapter
{
    public function __construct(
        LoggerInterface $logger,
        PdoConnection $connection,
        QueryFactory $simpleQueryFactory,
        ResultWriter $resultWriter,
        string $dataDir,
        array $state
    ) {
        parent::__construct($logger, $connection, $simpleQueryFactory, $resultWriter, $dataDir, $state);
    }

    public function getName(): string
    {
        return 'PDO';
    }

    /**
     * @throws ApplicationException
     * @throws UserExceptionInterface
     * @throws \Keboola\DbExtractorConfig\Exception\PropertyNotSetException
     */
    public function export(ExportConfig $exportConfig, string $csvFilePath): ExportResult
    {
        $query = $exportConfig->hasQuery() ? $exportConfig->getQuery() : $this->createSimpleQuery($exportConfig);
        $this->logger->warning(sprintf('Query: %s', $query));

        try {
            return $this->queryAndProcess(
                $query,
                $exportConfig->getMaxRetries(),
                function (QueryResult $result) use ($exportConfig, $csvFilePath) {
                    return $this->resultWriter->writeToCsv($result, $exportConfig, $csvFilePath);
                }
            );
        } catch (CsvException $e) {
            throw new ApplicationException('Failed writing CSV File: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (UserExceptionInterface $e) {
            throw $this->handleDbError($e, $exportConfig->getMaxRetries(), $exportConfig->getOutputTable());
        }
    }

}
