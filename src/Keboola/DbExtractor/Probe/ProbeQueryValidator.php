<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Probe;

use Keboola\DbExtractor\Exception\UserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\ExplainStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\ShowStatement;
use PhpMyAdmin\SqlParser\Statements\WithStatement;

/**
 * Read-only allowlist for the `probe` sync action: parses the SQL with
 * phpmyadmin/sql-parser and ensures it is exactly one statement of a
 * non-mutating kind. Resource limits (LIMIT, timeouts beyond the server
 * default) are intentionally left to the caller.
 */
final class ProbeQueryValidator
{
    /** @var array<int, class-string<Statement>> */
    private const ALLOWED_LEAF_STATEMENTS = [
        SelectStatement::class,
        ShowStatement::class,
        ExplainStatement::class, // also covers DESCRIBE / DESC
    ];

    public function validate(string $sql): void
    {
        $parser = new Parser($sql);

        if ($parser->errors !== []) {
            $first = $parser->errors[0];
            throw new UserException(sprintf(
                'Probe query could not be parsed: %s',
                $first->getMessage(),
            ));
        }

        $statements = $parser->statements;
        $count = count($statements);
        if ($count === 0) {
            throw new UserException('Probe query is empty.');
        }
        if ($count > 1) {
            throw new UserException(
                'Probe query must contain exactly one statement; multi-statement queries are not allowed.',
            );
        }

        $this->assertAllowed($statements[0]);
    }

    private function assertAllowed(Statement $statement): void
    {
        // CTEs (`WITH ... SELECT/UPDATE/DELETE`) are wrappers — recurse into the body so
        // a CTE with an UPDATE/DELETE underneath is still rejected.
        if ($statement instanceof WithStatement) {
            $inner = $statement->cteStatementParser?->statements ?? [];
            if ($inner === []) {
                throw new UserException(
                    'Probe query must be a SELECT, SHOW, DESCRIBE or EXPLAIN statement; '
                    . 'CTE body could not be parsed.',
                );
            }
            foreach ($inner as $innerStatement) {
                $this->assertAllowed($innerStatement);
            }
            return;
        }

        foreach (self::ALLOWED_LEAF_STATEMENTS as $allowed) {
            if ($statement instanceof $allowed) {
                return;
            }
        }

        throw new UserException(
            'Probe query must be a SELECT, SHOW, DESCRIBE or EXPLAIN statement; '
            . 'data- or schema-modifying statements are not allowed.',
        );
    }
}
