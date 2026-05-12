<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\DbExtractor\Exception\UserException;
use Keboola\DbExtractor\Probe\ProbeQueryValidator;
use PHPUnit\Framework\TestCase;

class ProbeQueryValidatorTest extends TestCase
{
    /**
     * @dataProvider allowedQueriesProvider
     */
    public function testAllowed(string $sql): void
    {
        (new ProbeQueryValidator())->validate($sql);
        $this->expectNotToPerformAssertions();
    }

    public function allowedQueriesProvider(): array
    {
        return [
            'simple select' => ['SELECT 1'],
            'select with join' => ['SELECT a.id FROM a JOIN b ON a.id = b.a_id WHERE b.x = 1'],
            'select with cte' => ['WITH t AS (SELECT 1 AS n) SELECT n FROM t'],
            'cte recursive' => ['WITH RECURSIVE t (n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM t WHERE n < 5) SELECT * FROM t'],
            'show tables' => ['SHOW TABLES'],
            'show full columns' => ['SHOW FULL COLUMNS FROM mytable'],
            'describe' => ['DESCRIBE mytable'],
            'desc shorthand' => ['DESC mytable'],
            'explain select' => ['EXPLAIN SELECT * FROM mytable'],
            'trailing semicolon and whitespace' => ["  SELECT 1;\n  "],
        ];
    }

    /**
     * @dataProvider rejectedQueriesProvider
     */
    public function testRejected(string $sql, string $expectedMessageFragment): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($expectedMessageFragment, '/') . '/i');
        (new ProbeQueryValidator())->validate($sql);
    }

    public function rejectedQueriesProvider(): array
    {
        return [
            'insert' => ['INSERT INTO t (a) VALUES (1)', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'update' => ['UPDATE t SET a = 1', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'delete' => ['DELETE FROM t WHERE a = 1', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'drop table' => ['DROP TABLE t', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'truncate' => ['TRUNCATE TABLE t', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'alter table' => ['ALTER TABLE t ADD COLUMN x INT', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'create table' => ['CREATE TABLE t (a INT)', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'set variable' => ['SET autocommit = 0', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'lock tables' => ['LOCK TABLES t WRITE', 'SELECT, SHOW, DESCRIBE or EXPLAIN'],
            'cte wrapping delete' => [
                'WITH t AS (SELECT id FROM a) DELETE FROM b WHERE id IN (SELECT id FROM t)',
                'SELECT, SHOW, DESCRIBE or EXPLAIN',
            ],
            'multi-statement' => ['SELECT 1; SELECT 2', 'exactly one statement'],
            'multi-statement select then drop' => ['SELECT 1; DROP TABLE t', 'exactly one statement'],
            'empty after comment' => ['-- just a comment', 'empty'],
            'whitespace only' => ['   ', 'empty'],
        ];
    }
}
