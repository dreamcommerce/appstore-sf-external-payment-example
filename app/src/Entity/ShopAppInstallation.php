<?php

namespace App\Entity;

use App\Exception\ShopAppTokenException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shop_app_installations')]
class ShopAppInstallation
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

    #[ORM\OneToMany(mappedBy: 'shopAppInstallation', targetEntity: ShopAppToken::class, cascade: ['persist', 'remove'])]
    private Collection $tokens;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $shop, string $shopUrl, int $applicationVersion, string $authCode)
    {
        $this->shop = $shop;
        $this->shopUrl = $this->normalizeShopUrl($shopUrl);
        $this->applicationVersion = $applicationVersion;
        $this->authCode = $authCode;
        $this->tokens = new ArrayCollection();
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

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getApplicationVersion(): int
    {
        return $this->applicationVersion;
    }

    public function setApplicationVersion(int $applicationVersion): void
    {
        $this->applicationVersion = $applicationVersion;
    }

    public function getAuthCode(): string
    {
        return $this->authCode;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, ShopAppToken>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function getActiveToken(): ShopAppToken
    {
        foreach ($this->tokens as $token) {
            if ($token->isActive()) {
                return $token;
            }
        }
        throw new ShopAppTokenException('No active token for the store: '. $this->shop);
    }

    public function addToken(ShopAppToken $token): void
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
        }
    }

    public function deactivateAllTokens(): void
    {
        foreach ($this->tokens as $token) {
            $token->deactivate(); // TODO implement deactivate method in ShopAppToken
        }
    }

    private function normalizeShopUrl(string $url): string
    {
        return preg_replace('#^https?://#', '', $url);
    }
}
