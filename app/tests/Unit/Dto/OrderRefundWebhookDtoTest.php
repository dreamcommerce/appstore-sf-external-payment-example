<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\Webhook\OrderRefundWebhookDto;
use PHPUnit\Framework\TestCase;

class OrderRefundWebhookDtoTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        // Arrange
        $refundId = 'refund-123';
        $transactionId = 'txn-789';
        $status = 'completed';
        $currencyId = 'PLN';
        $currencyValue = '50.00';
        $comment = 'Customer requested refund';
        $date = new \DateTimeImmutable('2025-09-11T15:30:00+00:00');

        // Act
        $dto = new OrderRefundWebhookDto(
            refundId: $refundId,
            transactionId: $transactionId,
            status: $status,
            currencyId: $currencyId,
            currencyValue: $currencyValue,
            comment: $comment,
            date: $date
        );

        // Assert
        $this->assertEquals($refundId, $dto->refundId);
        $this->assertEquals($transactionId, $dto->transactionId);
        $this->assertEquals($status, $dto->status);
        $this->assertEquals($currencyId, $dto->currencyId);
        $this->assertEquals($currencyValue, $dto->currencyValue);
        $this->assertEquals($comment, $dto->comment);
        $this->assertEquals($date, $dto->date);
    }

    public function testConstructorWithNullOptionalProperties(): void
    {
        // Act
        $dto = new OrderRefundWebhookDto(
            refundId: 'refund-123',
            transactionId: 'txn-789',
            status: 'completed',
            currencyId: 'PLN',
            currencyValue: '50.00'
        );

        // Assert
        $this->assertEquals('refund-123', $dto->refundId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('completed', $dto->status);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('50.00', $dto->currencyValue);
        $this->assertNull($dto->comment);
        $this->assertNull($dto->date);
    }

    public function testFromArrayWithAllFields(): void
    {
        // Arrange
        $data = [
            'refund_id' => 'refund-123',
            'transaction_id' => 'txn-789',
            'status' => 'completed',
            'currency_id' => 'PLN',
            'currency_value' => 50.00,
            'comment' => 'Customer requested refund',
            'date' => '2025-09-11T15:30:00+00:00'
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('refund-123', $dto->refundId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('completed', $dto->status);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('50.00', $dto->currencyValue);
        $this->assertEquals('Customer requested refund', $dto->comment);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->date);
        $this->assertEquals('2025-09-11T15:30:00+00:00', $dto->date->format('c'));
    }

    public function testFromArrayWithMissingOptionalFields(): void
    {
        // Arrange
        $data = [
            'refund_id' => 'refund-123',
            'transaction_id' => 'txn-789',
            'status' => 'completed',
            'currency_id' => 'PLN',
            'currency_value' => 50.00
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('refund-123', $dto->refundId);
        $this->assertEquals('txn-789', $dto->transactionId);
        $this->assertEquals('completed', $dto->status);
        $this->assertEquals('PLN', $dto->currencyId);
        $this->assertEquals('50.00', $dto->currencyValue);
        $this->assertNull($dto->comment);
        $this->assertNull($dto->date);
    }

    public function testFromArrayWithEmptyDateString(): void
    {
        // Arrange
        $data = [
            'refund_id' => 'refund-123',
            'transaction_id' => 'txn-789',
            'status' => 'completed',
            'currency_id' => 'PLN',
            'currency_value' => 50.00,
            'date' => '' // empty date string
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertNull($dto->date);
    }

    public function testFromArrayWithNullDate(): void
    {
        // Arrange
        $data = [
            'refund_id' => 'refund-123',
            'transaction_id' => 'txn-789',
            'status' => 'completed',
            'currency_id' => 'PLN',
            'currency_value' => 50.00,
            'date' => null
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertNull($dto->date);
    }

    public function testFromArrayConvertsNumericCurrencyValueToString(): void
    {
        // Arrange
        $data = [
            'refund_id' => 'refund-123',
            'transaction_id' => 'txn-789',
            'status' => 'completed',
            'currency_id' => 'PLN',
            'currency_value' => 123.45 // numeric value
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('123.45', $dto->currencyValue);
        $this->assertIsString($dto->currencyValue);
    }

    public function testFromArrayWithDifferentDateFormats(): void
    {
        // Test various date formats that might come from webhooks
        $dateFormats = [
            '2025-09-11T15:30:00+00:00',
            '2025-09-11 15:30:00',
            '2025-09-11T15:30:00Z',
            '2025-09-11T15:30:00.000Z'
        ];

        foreach ($dateFormats as $dateString) {
            // Arrange
            $data = [
                'refund_id' => 'refund-123',
                'transaction_id' => 'txn-789',
                'status' => 'completed',
                'currency_id' => 'PLN',
                'currency_value' => 50.00,
                'date' => $dateString
            ];

            // Act
            $dto = OrderRefundWebhookDto::fromArray($data);

            // Assert
            $this->assertInstanceOf(\DateTimeImmutable::class, $dto->date, "Failed for date format: $dateString");
            $this->assertEquals(2025, $dto->date->format('Y'), "Failed for date format: $dateString");
            $this->assertEquals(9, $dto->date->format('n'), "Failed for date format: $dateString");
            $this->assertEquals(11, $dto->date->format('j'), "Failed for date format: $dateString");
        }
    }

    public function testFromArrayWithCompletelyEmptyArray(): void
    {
        // Arrange
        $data = [];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('', $dto->refundId);
        $this->assertEquals('', $dto->transactionId);
        $this->assertEquals('', $dto->status);
        $this->assertEquals('', $dto->currencyId);
        $this->assertEquals('0.00', $dto->currencyValue);
        $this->assertNull($dto->comment);
        $this->assertNull($dto->date);
    }

    public function testFromArrayWithEmptyValues(): void
    {
        // Arrange
        $data = [
            'refund_id' => '',
            'transaction_id' => '',
            'status' => '',
            'currency_id' => '',
            'currency_value' => '0.00',
            'comment' => '',
            'date' => ''
        ];

        // Act
        $dto = OrderRefundWebhookDto::fromArray($data);

        // Assert
        $this->assertEquals('', $dto->refundId);
        $this->assertEquals('', $dto->transactionId);
        $this->assertEquals('', $dto->status);
        $this->assertEquals('', $dto->currencyId);
        $this->assertEquals('0.00', $dto->currencyValue);
        $this->assertEquals('', $dto->comment);
        $this->assertNull($dto->date);
    }
}
