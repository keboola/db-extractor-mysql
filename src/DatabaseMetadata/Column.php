<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\DatabaseMetadata;

class Column extends \Keboola\DbExtractorCommon\DatabaseMetadata\Column
{
    /** @var int */
    private $autoIncrement;

    /** @var string */
    private $constraintName;

    /** @var array */
    private $foreignKeyReference;

    /** @var string */
    private $description;

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        if ($this->autoIncrement) {
            $result['autoIncrement'] = $this->autoIncrement;
        }

        if ($this->constraintName) {
            $result['constraintName'] = $this->constraintName;
        }

        if ($this->description) {
            $result['description'] = $this->description;
        }

        if ($this->foreignKeyReference) {
            $result['foreignKeyRefSchema'] = $this->foreignKeyReference['schema'];
            $result['foreignKeyRefTable'] = $this->foreignKeyReference['table'];
            $result['foreignKeyRefColumn'] = $this->foreignKeyReference['column'];
        }

        return $result;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    public function setConstraintName(string $constraintName): void
    {
        $this->constraintName = $constraintName;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setForeignKeyReference(string $schema, string $table, string $column): void
    {
        $this->foreignKeyReference = [
            'schema' => $schema,
            'table' => $table,
            'column' => $column,
        ];
    }
}
