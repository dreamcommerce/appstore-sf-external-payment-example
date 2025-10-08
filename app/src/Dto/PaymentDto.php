<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentDto
{
    public function __construct(
        #[Assert\Type(type: ['int', 'null'], message: 'This value should be of type int|null.')]
        public readonly ?int $payment_id = null,

        #[Assert\NotBlank(message: "Name is required", groups: ["create"])]
        public readonly ?string $name = null,
        public readonly ?bool $visible = true,
        public readonly ?bool $active = true,
        public readonly array $currencies = [],
        public readonly array $supportedCurrencies = [],

        #[Assert\NotBlank(message: "Title is required", groups: ["create", "edit"])]
        public readonly ?string $title = null,

        public readonly ?string $description = '',
        public readonly ?string $locale = 'pl_PL'
    ) {
    }
}
