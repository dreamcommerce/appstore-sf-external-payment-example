<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shops_installed')]
class ShopInstalled
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $shop;

    #[ORM\Column(type: 'string', length: 255)]
    private string $shopUrl;

    #[ORM\Column(type: 'integer')]
    private int $applicationVersion;

    #[ORM\Column(type: 'string', length: 100)]
    private string $authCode;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tokens = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): string
    {
        return $this->shop;
    }

    public function setShop(string $shop): self
    {
        $this->shop = $shop;
        return $this;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function setShopUrl(string $shopUrl): self
    {
        $this->shopUrl = $shopUrl;
        return $this;
    }

    public function getApplicationVersion(): int
    {
        return $this->applicationVersion;
    }

    public function setApplicationVersion(int $applicationVersion): self
    {
        $this->applicationVersion = $applicationVersion;
        return $this;
    }

    public function getAuthCode(): string
    {
        return $this->authCode;
    }

    public function setAuthCode(string $authCode): self
    {
        $this->authCode = $authCode;
        return $this;
    }

    public function getTokens(): array
    {
        return $this->tokens ?? [];
    }

    public function setTokens(?array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
