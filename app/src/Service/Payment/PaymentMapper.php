<?php

namespace App\Service\Payment;

class PaymentMapper
{
    /**
     * Map API payment data to array for view layer
     */
    public function mapFromApi(array $paymentData, string $locale): array
    {
        $visible = isset($paymentData['visible']) ? ($paymentData['visible'] ? 'visible' : 'hidden') : 'hidden';
        $active = isset($paymentData['translations'][$locale]['active']) ? ($paymentData['translations'][$locale]['active'] ? 'active' : 'inactive') : 'inactive';
        $currencies = isset($paymentData['currencies']) ? json_encode($paymentData['currencies']) : '[]';
        $translationName = $paymentData['translations'][$locale]['title'] ?? '';

        return [
            'payment_id' => $paymentData['payment_id'],
            'name' => $translationName,
            'visible' => $visible,
            'active' => $active,
            'currencies' => $currencies,
        ];
    }
}

