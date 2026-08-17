<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    // A small table with DISTINCT integer sequence values (no ties) so the row order in the output
    // is fully deterministic across the whole MySQL version test matrix.
    $manager->createTable('incremental_window_numeric', [
        'id' => 'INT NOT NULL',
        'name' => 'VARCHAR(50) NOT NULL',
        'seq' => 'INT NOT NULL',
    ]);

    $manager->insertRows(
        'incremental_window_numeric',
        ['id', 'name', 'seq'],
        [
            // Below the numeric window start (900) -> excluded. Note the stored watermark is 800
            // (source/data/in/state.json); a plain watermark run would INCLUDE this row (seq >= 800).
            // Its absence from the output proves window mode uses the configured range, not the watermark.
            [1, 'below-start', 850],
            [2, 'in-low', 950],
            [3, 'in-mid', 1000],
            // At the numeric window end (1050) -> included (the upper bound is inclusive). This is also
            // the table MAX, so it is the value written back to state.
            [4, 'at-end', 1050],
        ],
    );
};
