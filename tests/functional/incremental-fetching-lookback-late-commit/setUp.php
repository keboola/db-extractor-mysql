<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    // A small table with DISTINCT integer sequence values (no ties) so the row order in the output
    // is fully deterministic across the whole MySQL version test matrix.
    $manager->createTable('incremental_lookback_late_commit', [
        'id' => 'INT NOT NULL',
        'name' => 'VARCHAR(50) NOT NULL',
        'seq' => 'INT NOT NULL',
    ]);

    $manager->insertRows(
        'incremental_lookback_late_commit',
        ['id', 'name', 'seq'],
        [
            // Beyond the lookback margin: "seq" (850) is more than the 100 lookback below the stored
            // watermark (1000), i.e. below the lower bound 900, so it is NOT re-fetched. This proves
            // the lookback is a BOUNDED re-scan, not a full rescan.
            [1, 'too-old', 850],
            // Late commit inside the lookback: "seq" (950) is BELOW the watermark (1000) but within
            // the 100 lookback (>= 900). Under the plain "col >= lastFetchedRow" behaviour it would be
            // permanently skipped; with the watermark-mode LOOKBACK it is re-scanned and must appear in
            // the output - the CFTL-771 late-commit fix.
            [2, 'late-row', 950],
            [3, 'watermark-row', 1000],
            [4, 'row-4', 1050],
        ],
    );
};
