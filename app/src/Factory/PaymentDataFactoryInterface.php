<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\Payment\CreatePaymentCommand;
use App\Dto\Payment\EditPaymentCommand;
use App\ValueObject\PaymentData;

interface PaymentDataFactoryInterface
{
    public function createFromCreateCommand(CreatePaymentCommand $command): PaymentData;

    public function createForNewPayment(
        string $title,
        string $description = null,
        bool $active = true,
        string $locale = 'pl_PL',
        string $notify = PaymentData::DEFAULT_NOTIFY_TEMPLATE,
        string $notifyMail = null,
        array $currencies = [1],
        array $supportedCurrencies = ['PLN']
    ): PaymentData;

    public function createForUpdate(
        array $updateData,
        string $locale = 'pl_PL'
    ): PaymentData;

    public function createForUpdateFromCommand(EditPaymentCommand $command, string $locale): PaymentData;
}
