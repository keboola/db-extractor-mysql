<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $connection = $test->getConnection();
    $connection->exec(
        'CREATE TABLE `native_types` (
            `c_tinyint` TINYINT,
            `c_smallint` SMALLINT,
            `c_mediumint` MEDIUMINT,
            `c_int` INT,
            `c_bigint` BIGINT,
            `c_decimal` DECIMAL(10,2),
            `c_float` FLOAT,
            `c_double` DOUBLE,
            `c_varchar` VARCHAR(20),
            `c_char` CHAR(5),
            `c_text` TEXT,
            `c_date` DATE,
            `c_datetime` DATETIME,
            `c_timestamp` TIMESTAMP NULL,
            `c_time` TIME,
            `c_year` YEAR
        )',
    );
    $connection->exec(
        "INSERT INTO `native_types` VALUES (
            1, 2, 3, 4, 5, 6.78, 1.5, 2.5, 'varchar', 'char', 'text',
            '2026-10-07', '2026-10-07 12:00:00', '2026-10-07 12:00:00', '12:00:00', 2026
        )",
    );
};
