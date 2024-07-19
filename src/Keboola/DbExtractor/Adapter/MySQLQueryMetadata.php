<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Adapter;

use Keboola\DbExtractor\Adapter\ValueObject\QueryMetadata;
use Keboola\DbExtractor\Extractor\MySQLDbConnection;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\ColumnBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\ColumnCollection;

class MySQLQueryMetadata implements QueryMetadata
{

    private MySQLDbConnection $connection;
    private string $query;

    public function __construct(MySQLDbConnection $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    public function getColumns(): ColumnCollection
    {
        $sql = sprintf(
            'CREATE TEMPORARY TABLE temp_table AS %s LIMIT 0;',
            rtrim(trim($this->query), ';'),
        );

        $this->connection->query($sql)->fetchAll();
        $columnsRaw = $this->connection->query('SHOW COLUMNS FROM temp_table')->fetchAll();
        $this->connection->query('DROP TEMPORARY TABLE temp_table');

        $columns = [];
        foreach ($columnsRaw as $data) {
            if (isset($data['Type']) && is_string($data['Type'])) {
                $type = $data['Type'];
                preg_match('/^([a-zA-Z]+)(\((\d+)\))?/', $type, $matches);
                $type = $matches[1] ?? '';
                $length = $matches[3] ?? null;
            } else {
                $type = '';
                $length = null;
            }

            if (isset($data['Field']) && is_string($data['Field'])) {
                $fieldName = $data['Field'];
            } else {
                $fieldName = '';
            }

            $builder = ColumnBuilder::create();
            $builder->setName($fieldName);
            $builder->setType($type);
            $builder->setLength($length);
            $columns[] = $builder->build();
        }

        return new ColumnCollection($columns);
    }
}
