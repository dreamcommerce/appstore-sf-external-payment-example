<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChannelDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Application channel ID is required", groups: ["create", "update"])]
        public readonly ?string $application_channel_id = null,
        public readonly ?string $type = null,

        #[Assert\NotBlank(message: "Name is required", groups: ["create", "update"])]
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $additional_info_label = null
    ) {
    }
}
