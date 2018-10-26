<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration;

use Keboola\DbExtractorCommon\Configuration\BaseExtractorConfig;

class Config extends BaseExtractorConfig
{
    public function isNetworkCompressionEnabled(): bool
    {
        return $this->getValue(['parameters', 'db', 'networkCompression']);
    }

    public function getSslParameters(): ?SslParameters
    {
        $dbParameters = $this->getValue(['parameters', 'db']);

        if (!isset($dbParameters['ssl'])) {
            return null;
        }
        return SslParameters::fromArray($dbParameters['ssl']);
    }
}
