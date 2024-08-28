<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\TraitTests\Tables;

use Keboola\DbExtractor\TraitTests\AddConstraintTrait;
use Keboola\DbExtractor\TraitTests\CreateTableTrait;
use Keboola\DbExtractor\TraitTests\InsertRowsTrait;

trait EscapingTableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;
    use AddConstraintTrait;

    public function createEscapingTable(string $name = 'escaping'): void
    {
        $this->createTable($name, $this->getEscapingColumns());
    }

    public function generateEscapingRows(string $tableName = 'escaping'): void
    {
        $data = $this->getEscapingRows();
        $this->insertRows($tableName, $data['columns'], $data['data']);
    }

    public function addEscapingConstraint(string $tableName = 'escaping', array $primaryKey = []): void
    {
        if ($primaryKey) {
            $this->addConstraint(
                $tableName,
                'PK_' . $tableName,
                'PRIMARY KEY',
                implode(', ', $primaryKey),
            );
        }
    }

    private function getEscapingRows(): array
    {
        return [
            'columns' => ['col1', 'col2'],
            'data' => [
                ['line with enclosure','second column'],
                ['column with enclosure \"\", and comma inside text','second column enclosure in text \"\"'],
                ['columns with
                new line','columns with 	tab'],
                ['column with backslash \ inside','column with backslash and enclosure \"\"'],
                ['column with \n \t \\\\','second col'],
                ['unicode characters','ľščťžýáíéúäôň'],
                ['first','something with

                double new line'],
            ],
        ];
    }

    private function getEscapingColumns(): array
    {
        return [
            'col1' => 'text CHARACTER SET utf8mb4 NULL',
            'col2' => 'text CHARACTER SET utf8mb4 NULL',
        ];
    }

    public function createBinaryConversionsTable(string $name = 'binary_conversions'): void
    {
        $this->createTable($name, $this->getBinaryColumns());
    }

    public function generateBinaryConversionsRows(string $tableName = 'binary_conversions'): void
    {
        $data = $this->getBinaryRows();
        $this->insertRows($tableName, $data['columns'], $data['data']);
    }

    private function getBinaryRows(): array
    {
        return [
            'columns' => ['bin1', 'bin2'],
            'data' => [
                ['0x3F1256', '0xFBD300'],
                ['0xAA4234', '0xF12C40'],
                ['0xFFA500', '0xF0A3DD'],
            ],
        ];
    }

    private function getBinaryColumns(): array
    {
        return [
            'bin1' => 'BINARY(3) NULL',
            'bin2' => 'BINARY(3) NULL',
        ];
    }
}
