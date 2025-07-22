<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shop_app_tokens')]
#[ORM\HasLifecycleCallbacks]
class ShopAppToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopAppInstallation::class, inversedBy: 'tokens')]
    #[ORM\JoinColumn(name: 'shop_app_installation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ShopAppInstallation $shopAppInstallation;

    #[ORM\Column(type: 'string', length: 255)]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ShopAppInstallation $shopAppInstallation,
        string $accessToken,
        string $refreshToken,
        \DateTimeImmutable $expiresAt,
        bool $isActive,
    ) {
        $this->shopAppInstallation = $shopAppInstallation;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = $isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopAppInstallation(): ShopAppInstallation
    {
        return $this->shopAppInstallation;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
