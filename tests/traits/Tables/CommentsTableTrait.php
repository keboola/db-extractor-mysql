<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\TraitTests\Tables;

use Keboola\DbExtractor\TraitTests\CreateTableTrait;
use Keboola\DbExtractor\TraitTests\InsertRowsTrait;
use Keboola\DbExtractor\TraitTests\QuoteIdentifierTrait;
use Keboola\DbExtractor\TraitTests\QuoteTrait;

/**
 * A table carrying table level and column level COMMENT values, plus a view over it.
 *
 * Deliberately a dedicated table rather than comments added to one of the shared
 * fixtures -- commenting those would change the expected manifests of every other
 * functional test.
 */
trait CommentsTableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;
    use QuoteIdentifierTrait;
    use QuoteTrait;

    public function createCommentsTable(string $name = 'comments'): void
    {
        $this->createTable($name, $this->getCommentsColumns());

        // COMMENT is a table option, it cannot be part of the column list
        $this->connection->prepare(sprintf(
            'ALTER TABLE %s COMMENT = %s',
            $this->quoteIdentifier($name),
            $this->quote('Table level comment'),
        ))->execute();
    }

    /**
     * MySQL reports the literal string "VIEW" as the TABLE_COMMENT of every view,
     * so a view is the fixture proving that value is not taken as a description.
     */
    public function createCommentsView(string $name = 'comments_view', string $table = 'comments'): void
    {
        $this->connection->prepare(sprintf(
            'CREATE VIEW %s AS SELECT `id`, `name` FROM %s',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table),
        ))->execute();
    }

    public function generateCommentsRows(string $tableName = 'comments'): void
    {
        $data = $this->getCommentsRows();
        $this->insertRows($tableName, $data['columns'], $data['data']);
    }

    private function getCommentsColumns(): array
    {
        return [
            'id' => 'INT NOT NULL COMMENT \'Surrogate key\'',
            'name' => 'VARCHAR(100) COMMENT \'Customer name\'',
            // "note" is intentionally left without a COMMENT
            'note' => 'VARCHAR(100)',
            // MySQL stores an empty COMMENT as an empty string, which must be
            // reported as no description rather than as an empty description
            'empty_comment' => 'VARCHAR(100) COMMENT \'\'',
        ];
    }

    private function getCommentsRows(): array
    {
        return [
            'columns' => ['id', 'name', 'note', 'empty_comment'],
            'data' => [
                [1, 'Alice', 'first', 'x'],
                [2, 'Bob', 'second', 'y'],
            ],
        ];
    }
}
