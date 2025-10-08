<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentDetailsContextDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Shop code is required")]
        public readonly string $shop,

        #[Assert\NotNull(message: "Payment ID is required", groups: ["details", "channel"])]
        public readonly ?int $id = null,
        public readonly ?string $translations = 'pl_PL'
    ) {
    }
}
