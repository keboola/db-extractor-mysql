<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

use Keboola\DbExtractor\Configuration\NodeDefinition\MysqlDbNode;
use Keboola\DbExtractor\Configuration\NodeDefinition\MysqlTableNodesDecorator;
use Keboola\DbExtractor\Configuration\ValueObject\MySQLExportConfig;
use Keboola\DbExtractor\Exception\UserException;
use Keboola\DbExtractor\Extractor\MySQL;
use Keboola\DbExtractorConfig\Config;
use Keboola\DbExtractorConfig\Configuration\ActionConfigRowDefinition;
use Keboola\DbExtractorConfig\Configuration\ConfigDefinition;
use Keboola\DbExtractorConfig\Configuration\ConfigRowDefinition;
use Throwable;

class MySQLApplication extends Application
{
    protected function loadConfig(): void
    {
        $config = $this->getRawConfig();
        $action = $config['action'] ?? 'run';

        $config['parameters']['extractor_class'] = 'MySQL';
        $config['parameters']['data_dir'] = $this->getDataDir();
        $dbNode = new MysqlDbNode();

        if ($this->isRowConfiguration($config)) {
            if ($action === 'run') {
                $this->config = new Config(
                    $config,
                    new ConfigRowDefinition($dbNode, null, null, new MysqlTableNodesDecorator()),
                );
            } else {
                $this->config = new Config($config, new ActionConfigRowDefinition($dbNode));
            }
        } else {
            $this->config = new Config(
                $config,
                new ConfigDefinition($dbNode, null, null, new MysqlTableNodesDecorator()),
            );
        }
    }

    protected function createExportConfig(array $data): MySQLExportConfig
    {
        return MySQLExportConfig::fromArray($data);
    }

    protected function getSyncActions(): array
    {
        return parent::getSyncActions() + ['query' => 'queryAction'];
    }

    protected function queryAction(): array
    {
        $params = $this->getConfig()->getParameters();
        $sql = $params['query'] ?? null;
        if (!is_string($sql) || trim($sql) === '') {
            throw new UserException("Parameter 'query' is required and must be a non-empty SQL string.");
        }

        $extractorFactory = new ExtractorFactory($params, $this->getInputState());
        $extractor = $extractorFactory->create(
            $this->getLogger(),
            $this->getConfig()->getAction(),
            $this->getConfig()->getDataTypeSupport(),
        );

        if (!$extractor instanceof MySQL) {
            throw new UserException('Unexpected extractor instance.');
        }

        try {
            $rows = $extractor->runRawQuery($sql);
        } catch (Throwable $e) {
            throw new UserException(sprintf('Query failed: %s', $e->getMessage()), 0, $e);
        }

        return [
            'status' => 'success',
            'rows' => $rows,
        ];
    }
}
