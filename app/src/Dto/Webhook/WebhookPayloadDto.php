<?php

declare(strict_types=1);

namespace App\Dto\Webhook;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for webhook payload validation
 */
class WebhookPayloadDto
{
    public function __construct(
        #[Assert\NotNull(message: 'Payload data cannot be null')]
        #[Assert\Type(type: 'array', message: 'Payload must be a valid JSON object')]
        public readonly array $data
    ) {
    }
}
