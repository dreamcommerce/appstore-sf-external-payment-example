<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ShopContextDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Shop code is required")]
        public readonly string $shop,
        public readonly ?string $translations = 'pl_PL'
    ) {
    }
}
