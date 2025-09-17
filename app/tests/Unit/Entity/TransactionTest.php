<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Entity\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private ShopPaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        $shop = $this->createMock(ShopAppInstallation::class);
        $this->paymentMethod = new ShopPaymentMethod($shop, 123);
    }

    public function testConstructorSetsRequiredProperties(): void
    {
        // Arrange
        $type = Transaction::TYPE_ORDER_TRANSACTION;
        $externalTransactionId = 'txn-789';
        $currencyId = 'PLN';
        $currencyValue = '99.99';

        // Act
        $transaction = new Transaction(
            $this->paymentMethod,
            $type,
            $externalTransactionId,
            $currencyId,
            $currencyValue
        );

        // Assert
        $this->assertEquals($this->paymentMethod, $transaction->getPaymentMethod());
        $this->assertEquals($type, $transaction->getType());
        $this->assertEquals($externalTransactionId, $transaction->getExternalTransactionId());
        $this->assertEquals($currencyId, $transaction->getCurrencyId());
        $this->assertEquals($currencyValue, $transaction->getCurrencyValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getCreatedAt());
        $this->assertNull($transaction->getId()); // ID is null before persistence
    }

    public function testTypeConstants(): void
    {
        // Assert
        $this->assertEquals('order-transaction.create', Transaction::TYPE_ORDER_TRANSACTION);
        $this->assertEquals('order-refund.create', Transaction::TYPE_ORDER_REFUND);
    }

    public function testIsOrderTransaction(): void
    {
        // Arrange
        $orderTransaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        $refundTransaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-456',
            'PLN',
            '50.00'
        );

        // Assert
        $this->assertTrue($orderTransaction->isOrderTransaction());
        $this->assertFalse($refundTransaction->isOrderTransaction());
    }

    public function testIsOrderRefund(): void
    {
        // Arrange
        $orderTransaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        $refundTransaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-456',
            'PLN',
            '50.00'
        );

        // Assert
        $this->assertFalse($orderTransaction->isOrderRefund());
        $this->assertTrue($refundTransaction->isOrderRefund());
    }

    public function testSetAndGetOrderId(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        // Act
        $transaction->setOrderId('order-456');

        // Assert
        $this->assertEquals('order-456', $transaction->getOrderId());
    }

    public function testSetAndGetExternalPaymentId(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        // Act
        $transaction->setExternalPaymentId('payment-789');

        // Assert
        $this->assertEquals('payment-789', $transaction->getExternalPaymentId());
    }

    public function testSetAndGetRefundId(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-123',
            'PLN',
            '50.00'
        );

        // Act
        $transaction->setRefundId('refund-456');

        // Assert
        $this->assertEquals('refund-456', $transaction->getRefundId());
    }

    public function testSetAndGetPaymentData(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );
        $paymentData = ['key' => 'value', 'amount' => 9999];

        // Act
        $transaction->setPaymentData($paymentData);

        // Assert
        $this->assertEquals($paymentData, $transaction->getPaymentData());
    }

    public function testSetAndGetPaymentSuccessShopLink(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );
        $link = 'https://shop.com/success';

        // Act
        $transaction->setPaymentSuccessShopLink($link);

        // Assert
        $this->assertEquals($link, $transaction->getPaymentSuccessShopLink());
    }

    public function testSetAndGetPaymentFailShopLink(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );
        $link = 'https://shop.com/fail';

        // Act
        $transaction->setPaymentFailShopLink($link);

        // Assert
        $this->assertEquals($link, $transaction->getPaymentFailShopLink());
    }

    public function testSetAndGetStatus(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-123',
            'PLN',
            '50.00'
        );

        // Act
        $transaction->setStatus('completed');

        // Assert
        $this->assertEquals('completed', $transaction->getStatus());
    }

    public function testSetAndGetComment(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-123',
            'PLN',
            '50.00'
        );
        $comment = 'Customer requested refund';

        // Act
        $transaction->setComment($comment);

        // Assert
        $this->assertEquals($comment, $transaction->getComment());
    }

    public function testSetAndGetTransactionDate(): void
    {
        // Arrange
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            'txn-123',
            'PLN',
            '50.00'
        );
        $date = new \DateTimeImmutable('2025-09-11T15:30:00+00:00');

        // Act
        $transaction->setTransactionDate($date);

        // Assert
        $this->assertEquals($date, $transaction->getTransactionDate());
    }

    public function testDefaultValues(): void
    {
        // Arrange & Act
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        // Assert
        $this->assertNull($transaction->getOrderId());
        $this->assertNull($transaction->getExternalPaymentId());
        $this->assertNull($transaction->getRefundId());
        $this->assertNull($transaction->getPaymentData());
        $this->assertNull($transaction->getPaymentSuccessShopLink());
        $this->assertNull($transaction->getPaymentFailShopLink());
        $this->assertNull($transaction->getStatus());
        $this->assertNull($transaction->getComment());
        $this->assertNull($transaction->getTransactionDate());
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        // Arrange
        $beforeCreation = new \DateTimeImmutable();

        // Act
        $transaction = new Transaction(
            $this->paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            'txn-123',
            'PLN',
            '99.99'
        );

        $afterCreation = new \DateTimeImmutable();

        // Assert
        $createdAt = $transaction->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertGreaterThanOrEqual($beforeCreation->getTimestamp(), $createdAt->getTimestamp());
        $this->assertLessThanOrEqual($afterCreation->getTimestamp(), $createdAt->getTimestamp());
    }
}
