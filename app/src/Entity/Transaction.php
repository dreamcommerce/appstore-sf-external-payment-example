<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[ORM\Index(columns: ['payment_method_id'], name: 'idx_transaction_payment_method')]
#[ORM\Index(columns: ['external_transaction_id'], name: 'idx_transaction_external_id')]
#[ORM\Index(columns: ['type'], name: 'idx_transaction_type')]
class Transaction
{
    public const TYPE_ORDER_TRANSACTION = 'order-transaction.create';
    public const TYPE_ORDER_REFUND = 'order-refund.create';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopPaymentMethod::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'payment_method_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ShopPaymentMethod $paymentMethod;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $orderId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $externalPaymentId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $externalTransactionId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $refundId = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $currencyId;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $currencyValue;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $paymentData = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $paymentSuccessShopLink = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $paymentFailShopLink = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $transactionDate = null;

    public function __construct(
        ShopPaymentMethod $paymentMethod,
        string $type,
        string $externalTransactionId,
        string $currencyId,
        string $currencyValue
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->type = $type;
        $this->externalTransactionId = $externalTransactionId;
        $this->currencyId = $currencyId;
        $this->currencyValue = $currencyValue;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentMethod(): ShopPaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getExternalPaymentId(): ?string
    {
        return $this->externalPaymentId;
    }

    public function setExternalPaymentId(?string $externalPaymentId): void
    {
        $this->externalPaymentId = $externalPaymentId;
    }

    public function getExternalTransactionId(): string
    {
        return $this->externalTransactionId;
    }

    public function getRefundId(): ?string
    {
        return $this->refundId;
    }

    public function setRefundId(?string $refundId): void
    {
        $this->refundId = $refundId;
    }

    public function getCurrencyId(): string
    {
        return $this->currencyId;
    }

    public function getCurrencyValue(): string
    {
        return $this->currencyValue;
    }

    public function getPaymentData(): ?array
    {
        return $this->paymentData;
    }

    public function setPaymentData(?array $paymentData): void
    {
        $this->paymentData = $paymentData;
    }

    public function getPaymentSuccessShopLink(): ?string
    {
        return $this->paymentSuccessShopLink;
    }

    public function setPaymentSuccessShopLink(?string $paymentSuccessShopLink): void
    {
        $this->paymentSuccessShopLink = $paymentSuccessShopLink;
    }

    public function getPaymentFailShopLink(): ?string
    {
        return $this->paymentFailShopLink;
    }

    public function setPaymentFailShopLink(?string $paymentFailShopLink): void
    {
        $this->paymentFailShopLink = $paymentFailShopLink;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTransactionDate(): ?\DateTimeImmutable
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?\DateTimeImmutable $transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    public function isOrderTransaction(): bool
    {
        return $this->type === self::TYPE_ORDER_TRANSACTION;
    }

    public function isOrderRefund(): bool
    {
        return $this->type === self::TYPE_ORDER_REFUND;
    }
}
