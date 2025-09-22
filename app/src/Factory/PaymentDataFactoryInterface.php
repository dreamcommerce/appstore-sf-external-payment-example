<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\Payment\CreatePaymentCommand;
use App\ValueObject\PaymentData;

interface PaymentDataFactoryInterface
{
    public function createFromCreateCommand(CreatePaymentCommand $command): PaymentData;
}
