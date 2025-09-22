<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $title,
        
        #[Assert\Type('string')]
        public readonly ?string $description,
        
        #[Assert\NotBlank]
        public readonly string $locale,
        
        #[Assert\Type('bool')]
        public readonly bool $active = true,
        
        #[Assert\Type('array')]
        public readonly array $currencies = []
    ) {
    }
}
