<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\Payment\CreatePaymentCommand;
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
}
