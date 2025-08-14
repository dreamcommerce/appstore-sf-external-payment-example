<?php

namespace App\Service\Payment;

interface PaymentServiceInterface
{
    /**
     * Creates a new payment for the shop
     */
    public function createPayment(string $shopCode, string $name, string $title, string $description, bool $visible, array $currencies, string $locale): void;

    /**
     * Updates an existing payment
     */
    public function updatePayment(string $shopCode, int $paymentId, array $data): void;

    /**
     * Deletes a payment
     */
    public function deletePayment(string $shopCode, int $paymentId): void;

    /**
     * Retrieves payment settings for the shop
     */
    public function getPaymentSettingsForShop(string $shopCode, string $locale): array;

    /**
     * Retrieves payment details by ID
     */
    public function getPaymentById(string $shopCode, int $paymentId, string $locale): ?array;
}
