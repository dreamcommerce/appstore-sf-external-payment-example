<?php

declare(strict_types=1);

namespace App\Domain\Shop\Model;

use App\Service\Event\AppStoreLifecycleEvent;

class Shop
{
    private string $shopId;
    private string $shopUrl;
    private int $version;
    private ?string $authCode;

    public function __construct(
        string $shopId, string $shopUrl, int $version, ?string $authCode = null
    )
    {
        $this->shopId = $shopId;
        $this->shopUrl = $shopUrl;
        $this->version = $version;
        $this->authCode = $authCode;
    }

    public static function fromEvent(AppStoreLifecycleEvent $event): self
    {
        return new self(
            $event->shopId,
            $event->shopUrl,
            $event->version, // Bez konwersji
            $event->authCode
        );
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getVersion(): int // Zmiana typu zwracanej wartości
    {
        return $this->version;
    }

    public function getAuthCode(): ?string
    {
        return $this->authCode;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->shopId,
            'url' => $this->shopUrl,
            'version' => $this->version
        ];

        if ($this->authCode !== null) {
            $data['auth_code'] = $this->authCode;
        }

        return $data;
    }
}
