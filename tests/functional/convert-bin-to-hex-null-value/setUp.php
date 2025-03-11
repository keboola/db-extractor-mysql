<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    // Create tables with binary columns including nullable foreign key
    $manager->createBinaryParentTable();
    $manager->generateBinaryParentRows();

    $manager->createBinaryChildTable();
    $manager->generateBinaryChildRows();
};
