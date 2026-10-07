<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\Exception\InvalidStateException;
use Keboola\DbExtractor\Adapter\ValueObject\QueryMetadata;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\ColumnBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\ColumnCollection;
use PDOStatement;

class MySQLQueryMetadata implements QueryMetadata
{
    // PDO MySQL driver reports protocol type names in "native_type", map them to SQL type names
    private const NATIVE_TYPE_TO_SQL_TYPE = [
        'TINY' => 'TINYINT',
        'SHORT' => 'SMALLINT',
        'INT24' => 'MEDIUMINT',
        'LONG' => 'INT',
        'LONGLONG' => 'BIGINT',
        'NEWDECIMAL' => 'DECIMAL',
        'NEWDATE' => 'DATE',
    ];

    private PDOStatement $stmt;

    private ?ColumnCollection $columns = null;

    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    public function getColumns(): ColumnCollection
    {
        if (!$this->columns) {
            $this->columns = $this->doGetColumns();
        }

        return $this->columns;
    }

    public static function mapNativeType(string $nativeType): string
    {
        return self::NATIVE_TYPE_TO_SQL_TYPE[strtoupper($nativeType)] ?? $nativeType;
    }

    private function doGetColumns(): ColumnCollection
    {
        $columnsCount = $this->stmt->columnCount();
        $columns = [];
        for ($i = 0; $i < $columnsCount; $i++) {
            /** @var array $columnMetadata */
            $columnMetadata = $this->stmt->getColumnMeta($i);

            if (!isset($columnMetadata['name'])) {
                throw new InvalidStateException('Missing key "name" in PDO query column\'s metadata.');
            }

            $builder = ColumnBuilder::create();
            $builder->setName($columnMetadata['name']);
            $builder->setType(self::mapNativeType((string) ($columnMetadata['native_type'] ?? 'string')));
            $columns[] = $builder->build();
        }

        return new ColumnCollection($columns);
    }
}
