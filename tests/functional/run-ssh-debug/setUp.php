<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return static function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());
    $manager->createSimpleTable();
};
