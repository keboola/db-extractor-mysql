<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\ValueObject\QueryMetadata;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\ColumnBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\ColumnCollection;

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

    private QueryMetadata $pdoQueryMetadata;

    private ?ColumnCollection $columns = null;

    public function __construct(QueryMetadata $pdoQueryMetadata)
    {
        $this->pdoQueryMetadata = $pdoQueryMetadata;
    }

    public function getColumns(): ColumnCollection
    {
        if (!$this->columns) {
            $columns = [];
            foreach ($this->pdoQueryMetadata->getColumns() as $column) {
                $columns[] = ColumnBuilder::create()
                    ->setName($column->getName())
                    ->setType(self::mapNativeType($column->getType()))
                    ->build();
            }
            $this->columns = new ColumnCollection($columns);
        }

        return $this->columns;
    }

    public static function mapNativeType(string $nativeType): string
    {
        return self::NATIVE_TYPE_TO_SQL_TYPE[strtoupper($nativeType)] ?? $nativeType;
    }
}
