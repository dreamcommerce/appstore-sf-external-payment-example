<?php

declare(strict_types=1);

namespace App\Security;

class HashValidator
{
    public function __construct(
        private readonly string $appStoreSecret
    ) {
    }

    /**
     * @param array<string, string> $requestHashParams
     */
    public function isValid(array $requestHashParams): bool
    {
        $hashFromRequest = $requestHashParams['hash'];
        unset($requestHashParams['hash']);

        ksort($requestHashParams);

        $keyValuesString = array_map(
            static fn ($key, $value) => sprintf('%s=%s', $key, $value),
            array_keys($requestHashParams),
            $requestHashParams
        );

        $generatedHash = hash_hmac('sha512', join('&', $keyValuesString), $this->appStoreSecret);

        return $generatedHash === $hashFromRequest;
    }
}
