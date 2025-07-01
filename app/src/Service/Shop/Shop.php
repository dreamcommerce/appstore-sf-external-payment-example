<?php

namespace App\Service\Shop;

class Shop
{
    private string $shopId;

    private string $shopUrl;
    private ?string $version;
    private ?string $authCode;

    public function __construct(
        string $shopId, string $shopUrl, ?string $version = null, ?string $authCode = null
    )
    {
        $this->shopId = $shopId;
        $this->shopUrl = $shopUrl;
        $this->version = $version;
        $this->authCode = $authCode;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getAuthCode(): ?string
    {
        return $this->authCode;
    }

    public static function fromEvent(\App\Service\Event\AppStoreLifecycleEvent $event): self
    {
        return new self(
            (string)$event->shopId,
            $event->shopUrl,
            $event->version,
            $event->authCode
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->shopId,
            'shop_url' => $this->shopUrl
        ];

        if ($this->version !== null) {
            $data['version'] = $this->version;
        }

        if ($this->authCode !== null) {
            $data['auth_code'] = $this->authCode;
        }

        return $data;
    }

    public function withAuthCode(string $authCode): self
    {
        return new self(
            $this->shopId, $this->shopUrl, $this->version, $authCode
        );
    }

    public function withVersion(string $version): self
    {
        return new self(
            $this->shopId, $this->shopUrl, $version, $this->authCode
        );
    }
}