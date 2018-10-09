<?php

declare(strict_types=1);

use Keboola\Component\UserException;
use Keboola\Component\Logger;
use Keboola\DbExtractor\MySQLApplication;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Monolog\Handler\NullHandler;

require_once(dirname(__FILE__) . "/../vendor/autoload.php");

$logger = new Logger();

$runAction = true;

try {
    $jsonDecode = new JsonDecode(true);

    $arguments = getopt("d::", ["data::"]);
    if (!isset($arguments["data"])) {
        throw new \Keboola\Component\UserException('Data folder not set.');
    }

    putenv(sprintf("KBC_DATADIR=%s", $arguments['data']));

    if (file_exists($arguments["data"] . "/config.json")) {
        $config = $jsonDecode->decode(
            file_get_contents($arguments["data"] . '/config.json'),
            JsonEncoder::FORMAT
        );
    } else {
        throw new UserException('Configuration file not found.');
    }

    // get the state
    $inputState = [];
    $inputStateFile = $arguments['data'] . '/in/state.json';
    if (file_exists($inputStateFile)) {
        $inputState = $jsonDecode->decode(
            file_get_contents($inputStateFile),
            JsonEncoder::FORMAT
        );
    }

    if ($config['action'] !== 'run') {
        $logger->setHandlers(array(new NullHandler(Logger::INFO)));
        $runAction = false;
    }

    $app = new \Keboola\ExMySql\MySqlExtractor($logger);
    $app->run();

    $logger->log('info', "Extractor finished successfully.");
    exit(0);
} catch (UserException $e) {
    $logger->log('error', $e->getMessage());
    if (!$runAction) {
        echo $e->getMessage();
    }
    exit(1);
} catch (\Throwable $e) {
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
