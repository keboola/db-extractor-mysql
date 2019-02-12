<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\DatabaseMetadata;

class Table extends \Keboola\DbExtractorCommon\DatabaseMetadata\Table
{
    /** @var string */
    private $description;

    /** @var int|null */
    private $rowCount;

    /** @var int|null */
    private $autoIncrement;

    /** @var string|null */
    private $timestampUpdateColumn;

    public function __construct(string $name, string $schema, string $type, int $rowCount)
    {
        parent::__construct($name, $schema, $type);
        $this->rowCount = $rowCount;
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();
        $result['rowCount'] = $this->rowCount;

        if ($this->description) {
            $result['description'] = $this->description;
        }

        if ($this->autoIncrement) {
            $result['autoIncrement'] = $this->autoIncrement;
        }

        if ($this->timestampUpdateColumn) {
            $result['timestampUpdateColumn'] = $this->timestampUpdateColumn;
        }

        return $result;
    }

    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    public function setTimestampUpdateColumn(string $timestampUpdateColumn): void
    {
        $this->timestampUpdateColumn = $timestampUpdateColumn;
    }
}
