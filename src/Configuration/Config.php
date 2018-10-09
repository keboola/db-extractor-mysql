<?php

declare(strict_types=1);

namespace Keboola\ExMySql\Configuration;

use Keboola\DbExtractorCommon\Configuration\BaseExtractorConfig;

class Config extends BaseExtractorConfig
{
    public function isNetworkCompression(): bool
    {
        return $this->getValue(['parameters', 'db', 'networkCompression']);
    }

    public function getSslParameters(): ?SslParameters
    {
        $dbParameters = $this->getValue(['parameters', 'db']);

        if (isset($dbParameters['ssl'])) {
            return SslParameters::fromRaw($dbParameters['ssl']);
        }
        return null;
    }
}
