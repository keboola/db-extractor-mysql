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

    public function __construct(bool $enabled, string $ca, string $cert, string $key, string $cipher, bool $verifyServerCert)
    {
        $this->enabled = $enabled;
        $this->ca = $ca;
        $this->cert = $cert;
        $this->key = $key;
        $this->cipher = $cipher;
        $this->verifyServerCert = $verifyServerCert;
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

    public function getCipher(): string
    {
        return $this->cipher;
    }

    public function getVerifyServerCert(): bool
    {
        return $this->verifyServerCert;
    }

    public static function fromArray(array $sslParameters): SslParameters
    {
        return new SslParameters(
            $sslParameters['enabled'],
            $sslParameters['ca'],
            $sslParameters['cert'],
            $sslParameters['key'],
            $sslParameters['cipher']
            $sslParameters['verifyServerCert']
        );
    }
}
