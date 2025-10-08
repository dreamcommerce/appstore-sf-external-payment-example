<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\Webhook\OrderTransactionWebhookDto;
use PHPUnit\Framework\TestCase;

class OrderTransactionWebhookDtoTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        // Arrange
        $orderId = 'order-123';
        $paymentId = '456';
        $transactionId = 'txn-789';
        $currencyId = 'PLN';
        $currencyValue = '99.99';
        $paymentData = json_encode(['key' => 'value', 'amount' => 9999]);
        $paymentSuccessShopLink = 'https://shop.com/success';
        $paymentFailShopLink = 'https://shop.com/fail';

        // Act
        $dto = new OrderTransactionWebhookDto(
            orderId: $orderId,
            paymentId: $paymentId,
            transactionId: $transactionId,
            currencyId: $currencyId,
            currencyValue: $currencyValue,
            paymentData: $paymentData,
            paymentSuccessShopLink: $paymentSuccessShopLink,
            paymentFailShopLink: $paymentFailShopLink
        );

        // Assert
        $this->assertEquals($orderId, $dto->orderId);
        $this->assertEquals($paymentId, $dto->paymentId);
        $this->assertEquals($transactionId, $dto->transactionId);
        $this->assertEquals($currencyId, $dto->currencyId);
        $this->assertEquals($currencyValue, $dto->currencyValue);
        $this->assertEquals($paymentData, $dto->paymentData);
        $this->assertEquals($paymentSuccessShopLink, $dto->paymentSuccessShopLink);
        $this->assertEquals($paymentFailShopLink, $dto->paymentFailShopLink);
    }

    public function testConstructorWithNullOptionalProperties(): void
    {
        // Act
        $dto = new OrderTransactionWebhookDto(
            orderId: 'order-123',
            paymentId: '456',
            transactionId: 'txn-789',
            currencyId: 'PLN',
            currencyValue: '99.99'
        );

        // Assert
        $this->assertEquals('order-123', $dto->orderId);
        $this->assertEquals('456', $dto->paymentId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('99.99', $dto->currencyValue);
        $this->assertNull($dto->paymentData);
        $this->assertNull($dto->paymentSuccessShopLink);
        $this->assertNull($dto->paymentFailShopLink);
    }

    public function testFromArrayWithAllFields(): void
    {
        // Arrange
        $data = [
            'order_id' => 'order-123',
            'payment_id' => '456',
            'transaction_id' => 'txn-789',
            'currency_id' => 'PLN',
            'currency_value' => 99.99,
            'payment_data' => ['key' => 'value'],
            'payment_success_shop_link' => 'https://shop.com/success',
            'payment_fail_shop_link' => 'https://shop.com/fail'
        ];

        // Act
        $dto = OrderTransactionWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('order-123', $dto->orderId);
        $this->assertEquals('456', $dto->paymentId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('99.99', $dto->currencyValue);
        $this->assertEquals(json_encode(['key' => 'value']), $dto->paymentData);
        $this->assertEquals('https://shop.com/success', $dto->paymentSuccessShopLink);
        $this->assertEquals('https://shop.com/fail', $dto->paymentFailShopLink);
    }

    public function testFromArrayWithMissingOptionalFields(): void
    {
        // Arrange
        $data = [
            'order_id' => 'order-123',
            'payment_id' => '456',
            'transaction_id' => 'txn-789',
            'currency_id' => 'PLN',
            'currency_value' => 99.99
        ];

        // Act
        $dto = OrderTransactionWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('order-123', $dto->orderId);
        $this->assertEquals('456', $dto->paymentId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('99.99', $dto->currencyValue);
        $this->assertNull($dto->paymentData);
        $this->assertNull($dto->paymentSuccessShopLink);
        $this->assertNull($dto->paymentFailShopLink);
    }

    public function testFromArrayWithEmptyValues(): void
    {
        // Arrange
        $data = [
            'order_id' => '',
            'payment_id' => '',
            'transaction_id' => '',
            'currency_id' => '',
            'currency_value' => '0.00'
        ];

        // Act
        $dto = OrderTransactionWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('', $dto->orderId);
        $this->assertEquals('', $dto->paymentId);
        $this->assertEquals('', $dto->transactionId);
        $this->assertEquals('', $dto->currencyId);
        $this->assertEquals('0.00', $dto->currencyValue);
    }

    public function testFromArrayConvertsNumericCurrencyValueToString(): void
    {
        // Arrange
        $data = [
            'order_id' => 'order-123',
            'payment_id' => '456',
            'transaction_id' => 'txn-789',
            'currency_id' => 'PLN',
            'currency_value' => 123.45 // numeric value
        ];

        // Act
        $dto = OrderTransactionWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('123.45', $dto->currencyValue);
        $this->assertIsString($dto->currencyValue);
    }

    public function testFromArrayWithCompletelyEmptyArray(): void
    {
        // Arrange
        $data = [];

        // Act
        $dto = OrderTransactionWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('', $dto->orderId);
        $this->assertEquals('', $dto->paymentId);
        $this->assertEquals('', $dto->transactionId);
        $this->assertEquals('', $dto->currencyId);
        $this->assertEquals('0.00', $dto->currencyValue);
        $this->assertNull($dto->paymentData);
        $this->assertNull($dto->paymentSuccessShopLink);
        $this->assertNull($dto->paymentFailShopLink);
    }
}
