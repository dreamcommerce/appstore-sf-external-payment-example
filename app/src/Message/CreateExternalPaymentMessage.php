<?php

namespace App\Message;

class CreateExternalPaymentMessage
{
    private string $shopCode;
    private string $shopUrl;
    private string $shopVersion;
    private string $name;
    private string $title;
    private string $description;
    private bool $visible;
    private array $currencies;
    private string $locale;

    public function __construct(
        string $shopCode,
        string $shopUrl,
        string $shopVersion,
        string $name,
        string $title,
        string $description,
        bool $visible,
        array $currencies,
        string $locale
    ) {
        $this->shopCode = $shopCode;
        $this->shopUrl = $shopUrl;
        $this->shopVersion = $shopVersion;
        $this->name = $name;
        $this->title = $title;
        $this->description = $description;
        $this->visible = $visible;
        $this->currencies = $currencies;
        $this->locale = $locale;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getShopVersion(): string
    {
        return $this->shopVersion;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
