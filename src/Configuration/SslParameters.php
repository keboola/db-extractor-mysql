<?php

declare(strict_types=1);

namespace Keboola\MysqlExtractor\Configuration;

class SslParameters
{
    /** @var bool */
    private $enabled;

    /** @var string */
    private $ca;

    /** @var string */
    private $cert;

    /** @var string */
    private $key;

    /** @var string|null */
    private $cipher;

    /** @var bool */
    private $verifyServerCert;

    public function __construct(array $sslParameters)
    {
        $this->enabled = $sslParameters['enabled'];
        $this->ca = $sslParameters['ca'];
        $this->cert = $sslParameters['cert'];
        $this->key = $sslParameters['key'];
        $this->cipher = $sslParameters['cipher'] ?? null;
        $this->verifyServerCert = $sslParameters['verifyServerCert'];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCa(): string
    {
        return $this->ca;
    }

    public function getCert(): string
    {
        return $this->cert;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCipher(): ?string
    {
        return $this->cipher;
    }

    public function getVerifyServerCert(): bool
    {
        return $this->verifyServerCert;
    }

    public static function fromRaw(array $sslParameters): SslParameters
    {
        return new SslParameters($sslParameters);
    }
}
