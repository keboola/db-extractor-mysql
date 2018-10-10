<?php

declare(strict_types=1);

use Keboola\Component\UserException;
use Keboola\Component\Logger;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Monolog\Handler\NullHandler;

require_once(dirname(__FILE__) . "/../vendor/autoload.php");

$logger = new Logger();

$runAction = true;

try {
    $dataDir = getenv('KBC_DATADIR') ?? '/data';
    $jsonDecode = new JsonDecode(true);

    if (file_exists($dataDir . "/config.json")) {
        $config = $jsonDecode->decode(
            (string) file_get_contents($dataDir . '/config.json'),
            JsonEncoder::FORMAT
        );
    } else {
        throw new UserException('Configuration file not found.');
    }

    // get the state
    $inputState = [];
    $inputStateFile = $dataDir . '/in/state.json';
    if (file_exists($inputStateFile)) {
        $inputState = $jsonDecode->decode(
            (string) file_get_contents($inputStateFile),
            JsonEncoder::FORMAT
        );
    }

    if ($config['action'] !== 'run') {
        $logger->setHandlers(array(new NullHandler(Logger::INFO)));
        $runAction = false;
    }

    $app = new \Keboola\MysqlExtractor\MysqlExtractor($logger);
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
