<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\TraitTests\Tables;

use Keboola\DbExtractor\TraitTests\AddConstraintTrait;
use Keboola\DbExtractor\TraitTests\CreateTableTrait;
use Keboola\DbExtractor\TraitTests\InsertRowsTrait;

trait BinaryNullableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;
    use AddConstraintTrait;

    public function createBinaryParentTable(string $name = 'binary_parent'): void
    {
        $this->createTable($name, $this->getBinaryParentColumns());
    }

    public function generateBinaryParentRows(string $tableName = 'binary_parent'): void
    {
        $data = $this->getBinaryParentRows();
        $this->insertRows($tableName, $data['columns'], $data['data']);
    }

    public function createBinaryChildTable(string $name = 'binary_child'): void
    {
        $this->createTable($name, $this->getBinaryChildColumns());
    }

    public function generateBinaryChildRows(string $tableName = 'binary_child'): void
    {
        $data = $this->getBinaryChildRows();
        $this->insertRows($tableName, $data['columns'], $data['data']);
    }

    private function getBinaryParentRows(): array
    {
        return [
            'columns' => ['id', 'bin_data'],
            'data' => [
                [1, '0x3F1256'],
                [2, '0xAA4234'],
                [3, '0xFFA500'],
            ],
        ];
    }

    private function getBinaryParentColumns(): array
    {
        return [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'bin_data' => 'BINARY(3) NOT NULL',
        ];
    }

    private function getBinaryChildRows(): array
    {
        return [
            'columns' => ['id', 'parent_bin_id', 'name'],
            'data' => [
                [1, '0x3F1256', 'Child 1'],
                [2, '0xAA4234', 'Child 2'],
                [3, null, 'Child with NULL FK'],  // This row has NULL foreign key
                [4, '0xFFA500', 'Child 4'],
            ],
        ];
    }

    private function getBinaryChildColumns(): array
    {
        return [
            'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'parent_bin_id' => 'BINARY(3) NULL',  // Nullable foreign key
            'name' => 'VARCHAR(50) NOT NULL',
        ];
    }
}
