<?php

declare(strict_types=1);

namespace App\Dto\Webhook;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for webhook headers validation
 */
class WebhookHeadersDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Shop license header is missing')]
        public readonly string $shopLicense,

        #[Assert\NotBlank(message: 'Webhook name header is missing')]
        #[Assert\Choice(
            choices: ['order-transaction.create', 'order-refund.create'],
            message: 'Invalid webhook type. Allowed types: order-transaction.create, order-refund.create'
        )]
        public readonly string $webhookType,

        #[Assert\NotBlank(message: 'Webhook ID header is missing')]
        public readonly string $webhookId,

        #[Assert\NotBlank(message: 'Webhook signature header is missing')]
        public readonly string $signature
    ) {
    }
}
