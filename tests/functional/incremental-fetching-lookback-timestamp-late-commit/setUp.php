<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseManager($test->getConnection());

    // A small table with DISTINCT datetimes (no ties) so the row order in the output is fully
    // deterministic across the whole MySQL version test matrix.
    $manager->createTable('incremental_lookback_ts', [
        'id' => 'INT NOT NULL',
        'name' => 'VARCHAR(50) NOT NULL',
        'ts' => 'DATETIME NOT NULL',
    ]);

    $manager->insertRows(
        'incremental_lookback_ts',
        ['id', 'name', 'ts'],
        [
            // Beyond the lookback margin: "ts" (09:45:00) is more than the 10-minute lookback below the
            // stored watermark (10:00:00), i.e. below the lower bound 09:50:00, so it is NOT re-fetched.
            // This proves the timestamp lookback is a BOUNDED re-scan, not a full rescan.
            [1, 'too-old', '2021-01-05 09:45:00'],
            // Late commit inside the lookback: "ts" (09:55:00) is BELOW the watermark (10:00:00) but
            // within the 10-minute lookback (>= 09:50:00). Under the plain "col >= lastFetchedRow"
            // behaviour it would be permanently skipped; with the watermark-mode LOOKBACK it is
            // re-scanned and must appear in the output - the CFTL-771 late-commit fix.
            [2, 'late-row', '2021-01-05 09:55:00'],
            [3, 'watermark-row', '2021-01-05 10:00:00'],
            [4, 'row-4', '2021-01-05 10:05:00'],
        ],
    );
};
