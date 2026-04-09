<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\Csv\CsvWriter;
use Keboola\Csv\Exception;

/**
 * CsvWriter that outputs NULL values as empty (unquoted) fields instead of quoted empty strings.
 * This means NULL -> ,, instead of ,"",
 */
class NullAwareCsvWriter extends CsvWriter
{
    public function rowToStr(array $row): string
    {
        $return = [];
        foreach ($row as $column) {
            if ($column === null) {
                $return[] = '';
                continue;
            }

            if (!(
                is_scalar($column)
                || (
                    is_object($column)
                    && method_exists($column, '__toString')
                )
            )) {
                throw new Exception(
                    'Cannot write data into column: ' . var_export($column, true),
                    Exception::WRITE_ERROR,
                );
            }

            $return[] = $this->getEnclosure() .
                str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), (string) $column) .
                $this->getEnclosure();
        }
        return implode($this->getDelimiter(), $return) . "\n";
    }
}
