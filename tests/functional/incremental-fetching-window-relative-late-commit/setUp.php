<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    // A small table with DISTINCT datetimes (no ties) so the row order in the output is fully
    // deterministic across the whole MySQL version test matrix.
    $manager->createTable('incremental_window_relative', [
        'id' => 'INT NOT NULL',
        'name' => 'VARCHAR(50) NOT NULL',
        'ts' => 'DATETIME NOT NULL',
    ]);

    $manager->insertRows(
        'incremental_window_relative',
        ['id', 'name', 'ts'],
        [
            [1, 'row-1', '2021-01-05 10:00:00'],
            [2, 'row-2', '2021-01-05 10:05:00'],
            // Late commit below the stored watermark (10:00:00). The config uses window mode with a
            // RELATIVE start ("-100 years"), which the resolver evaluates against "now" - always far
            // below these fixed 2021 rows - so the whole table is re-scanned and this row appears. The
            // exact resolved bound moves with the clock, but the output stays deterministic because the
            // bound is always earlier than every seeded row.
            [3, 'late-row', '2021-01-05 09:55:00'],
            [4, 'row-4', '2021-01-05 10:10:00'],
        ],
    );
};
