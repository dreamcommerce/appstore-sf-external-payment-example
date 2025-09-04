<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopPaymentMethodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopPaymentMethodRepository::class)]
#[ORM\Table(name: 'shop_payment_methods')]
#[ORM\Index(columns: ['shop_id', 'payment_method_id'], name: 'idx_shop_payment_method')]
class ShopPaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopAppInstallation::class)]
    #[ORM\JoinColumn(name: 'shop_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ShopAppInstallation $shop;

    #[ORM\Column(type: 'integer')]
    private int $paymentMethodId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $removedAt = null;

    public function __construct(ShopAppInstallation $shop, int $paymentMethodId)
    {
        $this->shop = $shop;
        $this->paymentMethodId = $paymentMethodId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): ShopAppInstallation
    {
        return $this->shop;
    }

    public function getPaymentMethodId(): int
    {
        return $this->paymentMethodId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRemovedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function setRemovedAt(?\DateTimeImmutable $removedAt): self
    {
        $this->removedAt = $removedAt;
        return $this;
    }
}
