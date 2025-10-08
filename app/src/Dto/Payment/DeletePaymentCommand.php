<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class DeletePaymentCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'integer', message: 'The payment ID must be an integer.')]
        public readonly int $payment_id
    ) {
    }
}
