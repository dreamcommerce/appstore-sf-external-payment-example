<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Dto\Webhook\WebhookRequestDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Service responsible for webhook signature verification
 */
class WebhookSignatureVerifier
{
    public function __construct(
        private readonly string $appstoreSecret,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Verifies the webhook signature
     *
     * @param WebhookRequestDto $webhookRequest DTO containing webhook data
     * @throws BadRequestHttpException When signature is invalid
     */
    public function verify(WebhookRequestDto $webhookRequest): void
    {
        $expectedSignature = sha1(
            $webhookRequest->webhookId . ":" .
            $this->appstoreSecret . ":" .
            $webhookRequest->rawPayload
        );

        if (!hash_equals($expectedSignature, $webhookRequest->signature)) {
            $this->logger->warning('Invalid webhook signature', [
                'shop_license' => $webhookRequest->shopLicense,
                'webhook_type' => $webhookRequest->webhookType
            ]);
            throw new BadRequestHttpException('Invalid webhook signature');
        }
    }
}
