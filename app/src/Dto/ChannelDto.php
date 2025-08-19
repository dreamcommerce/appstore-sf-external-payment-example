<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChannelDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Application channel ID is required", groups: ["create", "update"])]
        public readonly ?string $application_channel_id = '',
        public readonly ?string $type = null,

        #[Assert\NotBlank(message: "Name is required", groups: ["create", "update"])]
        public readonly ?string $name = '',
        public readonly ?string $description = '',
        public readonly ?string $additional_info_label = ''
    ) {
    }
}
