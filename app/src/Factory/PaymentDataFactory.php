<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\Payment\CreatePaymentCommand;
use App\Dto\Payment\EditPaymentCommand;
use App\ValueObject\PaymentData;

class PaymentDataFactory implements PaymentDataFactoryInterface
{
    public function createFromCreateCommand(CreatePaymentCommand $command): PaymentData
    {
        $translations = [
            $command->locale => [
                'title' => $command->title,
                'description' => $command->description ?? '',
                'active' => $command->active,
                'notify' => null,
            ]
        ];

        return new PaymentData(
            'external',
            $translations,
            $command->currencies,
            [] // Supported currencies will be handled by PaymentService
        );
    }

    public function createForNewPayment(
        string $title,
        string $description = null,
        bool $active = true,
        string $locale = 'pl_PL',
        string $notify = PaymentData::DEFAULT_NOTIFY_TEMPLATE,
        string $notifyMail = null,
        array $currencies = [1],
        array $supportedCurrencies = ['PLN']
    ): PaymentData {
        $translations = [
            $locale => [
                'title' => $title,
                'active' => $active
            ]
        ];

        if ($description !== null) {
            $translations[$locale]['description'] = $description;
        }

        if ($notify !== null) {
            $translations[$locale]['notify'] = $notify;
        }

        if ($notifyMail !== null) {
            $translations[$locale]['notify_mail'] = $notifyMail;
        }

        return new PaymentData('external', $translations, $currencies, $supportedCurrencies);
    }

    public function createForUpdate(
        array $updateData,
        string $locale = 'pl_PL'
    ): PaymentData {
        $name = $updateData['name'] ?? 'external';
        $translations = $updateData['translations'] ?? [];
        $currencies = $updateData['currencies'] ?? [];
        $supportedCurrencies = $updateData['supportedCurrencies'] ?? [];

        if (empty($translations) && isset($updateData['title'])) {
            $translations[$locale]['title'] = $updateData['title'];
        }

        if (isset($updateData['active'])) {
            $translations[$locale]['active'] = (bool) $updateData['active'];
        }

        if (isset($updateData['description'])) {
            $translations[$locale]['description'] = $updateData['description'];
        }

        if (isset($updateData['notify'])) {
            $translations[$locale]['notify'] = $updateData['notify'];
        }

        if (isset($updateData['notify_mail'])) {
            $translations[$locale]['notify_mail'] = $updateData['notify_mail'];
        }

        return new PaymentData($name, $translations, $currencies, $supportedCurrencies);
    }

    public function createForUpdateFromCommand(EditPaymentCommand $command, string $locale): PaymentData
    {
        return $this->createForUpdate(
            [
                'currencies' => $command->currencies,
                'visible' => $command->visible,
                'active' => $command->active,
                'translations' => [
                    $locale => [
                        'title' => $command->title,
                        'description' => $command->description,
                        'active' => $command->active,
                    ]
                ]
            ],
            $locale
        );
    }
}
