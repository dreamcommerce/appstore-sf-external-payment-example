<?php

declare(strict_types=1);

namespace App\Dto\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for complete webhook request including headers and payload
 */
class WebhookRequestDto
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
        public readonly string $signature,

        #[Assert\NotNull(message: 'Payload data cannot be null')]
        #[Assert\Type(type: 'array', message: 'Payload must be a valid JSON object')]
        public readonly array $payload,

        #[Assert\NotBlank(message: 'Raw payload is missing')]
        public readonly string $rawPayload
    ) {
    }

    public static function fromRequest(Request $request): ?self
    {
        $rawPayload = $request->getContent();
        $payload = json_decode($rawPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return null;
        }

        return new self(
            shopLicense: $request->headers->get('x-shop-license', ''),
            webhookType: $request->headers->get('x-webhook-name', ''),
            webhookId: $request->headers->get('x-webhook-id', ''),
            signature: $request->headers->get('x-webhook-sha1', ''),
            payload: $payload,
            rawPayload: $rawPayload
        );
    }
}
