<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    $manager->createCommentsTable();
    $manager->generateCommentsRows();

    // MySQL reports the literal string "VIEW" as the TABLE_COMMENT of every view
    $manager->createCommentsView();
};
