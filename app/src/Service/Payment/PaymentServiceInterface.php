<?php

namespace App\Service\Payment;

use App\Domain\Shop\Model\Shop;

/**
 * Interfejs dla serwisu płatności
 */
interface PaymentServiceInterface
{
    /**
     * Tworzy nową płatność dla sklepu
     */
    public function createPayment(string $shopCode, string $name, string $title, string $description, bool $visible, array $currencies, string $locale): bool;

    /**
     * Aktualizuje istniejącą płatność
     */
    public function updatePayment(string $shopCode, int $paymentId, array $data): bool;

    /**
     * Usuwa płatność
     */
    public function deletePayment(string $shopCode, int $paymentId): bool;

    /**
     * Pobiera ustawienia płatności dla sklepu
     */
    public function getPaymentSettingsForShop(string $shopCode, string $locale): array;

    /**
     * Pobiera szczegóły płatności po ID
     */
    public function getPaymentById(string $shopCode, int $paymentId, string $locale): ?array;
}
