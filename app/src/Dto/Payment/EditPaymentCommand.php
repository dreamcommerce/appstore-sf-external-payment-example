<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class EditPaymentCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly int $payment_id,

        #[Assert\Type('bool')]
        public readonly bool $visible,
        
        #[Assert\Type('bool')]
        public readonly bool $active,
        
        #[Assert\NotBlank]
        public readonly string $title,
        
        #[Assert\Type('string')]
        public readonly ?string $description,
        
        #[Assert\Type('array')]
        public readonly array $currencies = [],
        
        #[Assert\NotBlank]
        public readonly string $name = '',

        #[Assert\NotBlank]
        #[Assert\Locale]
        public readonly string $locale = 'en_US'
    ) {
    }
}
