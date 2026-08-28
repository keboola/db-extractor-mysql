<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\TraitTests;

use PDO;

trait RemoveAllTablesTrait
{
    use QuoteIdentifierTrait;

    protected PDO $connection;

    protected function removeAllTables(): void
    {
        $this->removeAllViews();

        $sql = <<<SQL
          SET FOREIGN_KEY_CHECKS = 0; 
          SET @tables = NULL;
          SET GROUP_CONCAT_MAX_LEN=131071;
        
          SELECT GROUP_CONCAT('`', table_schema, '`.`', table_name, '`') INTO @tables
          FROM   information_schema.tables 
          WHERE  TABLE_SCHEMA NOT IN ("performance_schema", "mysql", "information_schema", "sys");
          SELECT IFNULL(@tables, '') INTO @tables;
        
          SET        @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
          PREPARE    stmt FROM @tables;
          EXECUTE    stmt;
          DEALLOCATE PREPARE stmt;
          SET        FOREIGN_KEY_CHECKS = 1;
        SQL;

        $this->connection->query($sql);
    }

    /**
     * Views have to be dropped separately. "DROP TABLE" leaves a view in place and only
     * emits a warning, which PDO does not surface, so a view created by one test would
     * otherwise show up in the metadata of every test that follows it.
     */
    protected function removeAllViews(): void
    {
        $result = $this->connection->query(
            'SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.views ' .
            'WHERE TABLE_SCHEMA NOT IN ("performance_schema", "mysql", "information_schema", "sys")',
        );

        if ($result === false) {
            return;
        }

        /** @var array<array{TABLE_SCHEMA: string, TABLE_NAME: string}> $views */
        $views = $result->fetchAll(PDO::FETCH_ASSOC);
        foreach ($views as $view) {
            $this->connection->query(sprintf(
                'DROP VIEW IF EXISTS %s.%s',
                $this->quoteIdentifier($view['TABLE_SCHEMA']),
                $this->quoteIdentifier($view['TABLE_NAME']),
            ));
        }
    }
}
