<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Webhook\WebhookHeadersDto;
use App\Dto\Webhook\WebhookPayloadDto;
use App\Dto\Webhook\WebhookRequestDto;
use App\Message\ProcessWebhookMessage;
use App\Service\Payment\WebhookSignatureVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly WebhookSignatureVerifier $signatureVerifier,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/webhook/payment', name: 'webhook_payment', methods: ['POST'])]
    public function handlePaymentWebhook(
        Request $request
    ): JsonResponse {
        try {
            $content = $request->getContent();
            $contentType = $request->headers->get('Content-Type', '');

            if (!$content || !str_contains($contentType, 'application/json')) {
                $this->logger->warning('Invalid webhook content type', [
                    'content_type' => $contentType,
                    'content_length' => $request->headers->get('Content-Length')
                ]);
                throw new BadRequestHttpException('Invalid content type. Expected application/json.');
            }

            $payload = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
                $this->logger->warning('Invalid webhook payload format', [
                    'json_error' => json_last_error_msg(),
                    'content_type' => $contentType
                ]);
                throw new BadRequestHttpException('Invalid payload format. Expected valid JSON object.');
            }

            $payloadDto = new WebhookPayloadDto($payload);
            $violations = $this->validator->validate($payloadDto);
            if (count($violations) > 0) {
                $errorMessages = [];
                foreach ($violations as $violation) {
                    $errorMessages[] = $violation->getMessage();
                }

                $this->logger->warning('Invalid webhook payload', [
                    'errors' => $errorMessages
                ]);

                throw new BadRequestHttpException(implode(', ', $errorMessages));
            }

            $headersDto = $this->extractAndValidateHeaders($request);
            $webhookRequestDto = WebhookRequestDto::fromRequest($request);

            if (!$webhookRequestDto) {
                $this->logger->warning('Invalid webhook payload format', [
                    'content_type' => $request->headers->get('Content-Type'),
                    'content_length' => $request->headers->get('Content-Length')
                ]);
                throw new BadRequestHttpException('Invalid payload format. Expected valid JSON.');
            }

            $this->signatureVerifier->verify($webhookRequestDto);
            $this->logger->info('Processing webhook', [
                'type' => $headersDto->webhookType,
                'shop_license' => $headersDto->shopLicense,
                'payload_keys' => array_keys($payloadDto->data)
            ]);

            $this->messageBus->dispatch(
                new ProcessWebhookMessage(
                    $headersDto->webhookType,
                    $headersDto->shopLicense,
                    $payloadDto->data
                )
            );

            return new JsonResponse(
                ['status' => 'accepted', 'message' => 'Webhook received and queued for processing'],
                Response::HTTP_ACCEPTED
            );
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error processing webhook', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new BadRequestHttpException('Error processing webhook');
        }
    }

    /**
     * Extracts and validates webhook headers from the request
     *
     * @throws BadRequestHttpException When headers are invalid
     */
    private function extractAndValidateHeaders(Request $request): WebhookHeadersDto
    {
        $headersDto = new WebhookHeadersDto(
            shopLicense: $request->headers->get('x-shop-license', ''),
            webhookType: $request->headers->get('x-webhook-name', ''),
            webhookId: $request->headers->get('x-webhook-id', ''),
            signature: $request->headers->get('x-webhook-sha1', '')
        );

        $violations = $this->validator->validate($headersDto);
        if (count($violations) > 0) {
            $errorMessages = [];
            foreach ($violations as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            $this->logger->warning('Invalid webhook headers', [
                'errors' => $errorMessages
            ]);

            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        return $headersDto;
    }
}
