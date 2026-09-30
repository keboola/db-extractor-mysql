<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\DbExtractor\Extractor\MySQLDbConnectionFactory;
use Keboola\DbExtractor\Extractor\MySQLMetadataProvider;
use Keboola\DbExtractor\FunctionalTests\PdoTestConnection;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Table;
use Keboola\DbExtractor\TraitTests\RemoveAllTablesTrait;
use Keboola\DbExtractor\TraitTests\Tables\CommentsTableTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MySQLMetadataProviderTest extends TestCase
{
    use CommentsTableTrait;
    use RemoveAllTablesTrait;

    protected PDO $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = PdoTestConnection::createConnection();
        $this->removeAllTables();

        $this->createCommentsTable();
        $this->generateCommentsRows();
        $this->createCommentsView();
    }

    public function testCommentsArePropagatedAsDescriptions(): void
    {
        $table = $this->getTableMetadata('comments');

        $this->assertTrue($table->hasDescription());
        $this->assertSame('Table level comment', $table->getDescription());

        $columns = $table->getColumns();
        $this->assertSame('Surrogate key', $columns->getByName('id')->getDescription());
        $this->assertSame('Customer name', $columns->getByName('name')->getDescription());

        // No COMMENT at all
        $this->assertFalse($columns->getByName('note')->hasDescription());
        // COMMENT '' is stored as an empty string by MySQL, which must not become a description
        $this->assertFalse($columns->getByName('empty_comment')->hasDescription());
    }

    public function testViewHasNoTableDescription(): void
    {
        $view = $this->getTableMetadata('comments_view');

        // MySQL reports the literal string "VIEW" as the TABLE_COMMENT of every view
        $this->assertFalse($view->hasDescription());

        // Column comments are copied over from the underlying table by MySQL, so they stay
        $columns = $view->getColumns();
        $this->assertSame('Surrogate key', $columns->getByName('id')->getDescription());
        $this->assertSame('Customer name', $columns->getByName('name')->getDescription());
    }

    public function testDisabledPropagationReadsNoDescriptions(): void
    {
        $table = $this->getTableMetadata('comments', false);

        $this->assertFalse($table->hasDescription());
        foreach ($table->getColumns() as $column) {
            $this->assertFalse($column->hasDescription());
        }
    }

    private function getTableMetadata(string $tableName, bool $propagateDescriptions = true): Table
    {
        $provider = new MySQLMetadataProvider(
            MySQLDbConnectionFactory::create(PdoTestConnection::createDbConfig(), new NullLogger(), 1),
            (string) getenv('MYSQL_DB_DATABASE'),
            $propagateDescriptions,
        );

        return $provider->listTables()->getByNameAndSchema($tableName, (string) getenv('MYSQL_DB_DATABASE'));
    }
}
