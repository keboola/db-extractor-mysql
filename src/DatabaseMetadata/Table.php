<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\DatabaseMetadata;

class Table extends \Keboola\DbExtractorCommon\DatabaseMetadata\Table
{
    /** @var string */
    private $description;

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        if ($this->description) {
            $result['description'] = $this->description;
        }

        return $result;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
