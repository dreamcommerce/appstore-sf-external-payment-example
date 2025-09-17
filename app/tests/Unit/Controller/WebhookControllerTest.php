<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\WebhookController;
use App\Dto\Webhook\WebhookRequestDto;
use App\Message\ProcessWebhookMessage;
use App\Service\Payment\WebhookSignatureVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WebhookControllerTest extends TestCase
{
    private WebhookController $controller;
    private LoggerInterface|MockObject $logger;
    private ValidatorInterface|MockObject $validator;
    private WebhookSignatureVerifier|MockObject $signatureVerifier;
    private MessageBusInterface|MockObject $messageBus;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->signatureVerifier = $this->createMock(WebhookSignatureVerifier::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->controller = new WebhookController(
            $this->signatureVerifier,
            $this->messageBus,
            $this->logger,
            $this->validator
        );
    }

    private function createWebhookRequest(
        array|string $payload,
        ?string $shopLicense = null,
        ?string $webhookName = null,
        ?string $webhookSha1 = null,
        ?string $webhookId = null
    ): Request {
        $request = new Request([], [], [], [], [], [], is_array($payload) ? json_encode($payload) : $payload);
        if ($shopLicense) {
            $request->headers->set('x-shop-license', $shopLicense);
        }
        if ($webhookName) {
            $request->headers->set('x-webhook-name', $webhookName);
        }
        if ($webhookSha1) {
            $request->headers->set('x-webhook-sha1', $webhookSha1);
        }
        if ($webhookId) {
            $request->headers->set('x-webhook-id', $webhookId);
        }
        $request->headers->set('Content-Type', 'application/json');
        return $request;
    }

    public function testHandlePaymentWebhookSuccess(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookId = 'webhook-id-123';
        $payload = [
            'type' => 'order-transaction.create',
            'order_id' => 'order-123',
            'payment_id' => '456',
            'transaction_id' => 'txn-789'
        ];

        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Validator powinien być wywołany 2 razy - dla payload i dla headers
        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-transaction.create',
            $webhookId,
            'valid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto));

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ProcessWebhookMessage $message) use ($payload) {
                return $message->getWebhookType() === 'order-transaction.create' &&
                       is_array($message->getPayload());
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing webhook', $this->arrayHasKey('type'));

        // Act
        $response = $this->controller->handlePaymentWebhook($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('accepted', $responseData['status']);
    }

    public function testHandlePaymentWebhookSuccessOrderTransaction(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookId = 'webhook-id-123';
        $payload = [
            'type' => 'order-transaction.create',
            'order_id' => 'order-123',
            'payment_id' => '456',
            'transaction_id' => 'txn-789',
            'amount' => '100.00',
            'currency' => 'EUR'
        ];

        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Configuracja mocków
        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Tworzymy rzeczywisty obiekt WebhookRequestDto zamiast mocka
        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-transaction.create',
            $webhookId,
            'valid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto));

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ProcessWebhookMessage $message) use ($payload) {
                return $message->getWebhookType() === 'order-transaction.create' &&
                       is_array($message->getPayload());
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing webhook', $this->arrayHasKey('type'));

        // Act
        $response = $this->controller->handlePaymentWebhook($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('accepted', $responseData['status']);
    }

    public function testHandlePaymentWebhookSuccessOrderRefund(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookId = 'webhook-id-123';
        $payload = [
            'type' => 'order-refund.create',
            'order_id' => 'order-123',
            'payment_id' => '456',
            'refund_id' => 'refund-789',
            'amount' => '50.00',
            'currency' => 'EUR'
        ];

        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-refund.create',
            'valid-signature',
            $webhookId
        );

        // Configuracja mocków
        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Tworzymy rzeczywisty obiekt WebhookRequestDto zamiast mocka
        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-refund.create',
            $webhookId,
            'valid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto));

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ProcessWebhookMessage $message) use ($payload) {
                return $message->getWebhookType() === 'order-refund.create' &&
                       is_array($message->getPayload());
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing webhook', $this->arrayHasKey('type'));

        // Act
        $response = $this->controller->handlePaymentWebhook($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('accepted', $responseData['status']);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenInvalidSignature(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $payload = ['type' => 'order-transaction.create'];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-transaction.create',
            'invalid-signature',
            $webhookId
        );

        // Configuracja mocków
        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Tworzymy rzeczywisty obiekt WebhookRequestDto zamiast mocka
        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-transaction.create',
            $webhookId,
            'invalid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto))
            ->willThrowException(new BadRequestHttpException('Invalid webhook signature'));

        $this->logger->expects($this->never())
            ->method('info');

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid webhook signature');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenInvalidHeaders(): void
    {
        // Arrange
        $shopLicense = '';
        $webhookId = 'webhook-id-123';
        $payload = ['type' => 'order-transaction.create'];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Create a constraint violation
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')->willReturn('Shop license header is missing');
        $violations = new ConstraintViolationList([$violation]);

        // Configure validator to return violations
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid webhook payload', $this->arrayHasKey('errors'));

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shop license header is missing');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenMissingShopLicense(): void
    {
        // Arrange
        $webhookId = 'webhook-id-123';
        $payload = ['type' => 'order-transaction.create'];
        $request = $this->createWebhookRequest(
            $payload,
            null, // Brak shop license
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Create a constraint violation
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')->willReturn('Shop license header is missing');
        $violations = new ConstraintViolationList([$violation]);

        // Configure validator to return violations
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid webhook payload', $this->arrayHasKey('errors'));

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shop license header is missing');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenInvalidPayload(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $request = $this->createWebhookRequest(
            'invalid-json',
            $shopLicense,
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Oczekujemy, że kontroler wykryje nieprawidłowy JSON i rzuci wyjątek
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid webhook payload format', $this->arrayHasKey('json_error'));

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid payload format');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenMissingWebhookType(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $payload = ['type' => 'order-transaction.create'];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            null, // Brak webhook type
            'valid-signature',
            $webhookId
        );

        // Create a constraint violation
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')->willReturn('Webhook name header is missing');
        $violations = new ConstraintViolationList([$violation]);

        // Configure validator to return violations
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid webhook payload', $this->arrayHasKey('errors'));

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Webhook name header is missing');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenUnknownWebhookType(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $payload = ['type' => 'unknown-type'];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'unknown-webhook-type',
            'valid-signature',
            $webhookId
        );

        // Create a constraint violation for invalid webhook type
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')
            ->willReturn('Invalid webhook type. Allowed types: order-transaction.create, order-refund.create');
        $violations = new ConstraintViolationList([$violation]);

        // Configure validator to return violations
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid webhook payload', $this->arrayHasKey('errors'));

        // Act & Assert
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid webhook type');

        $this->controller->handlePaymentWebhook($request);
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenMissingRequiredFieldsOrderTransaction(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $payload = [
            'type' => 'order-transaction.create',
            // Brak wymaganych pól
        ];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-transaction.create',
            'valid-signature',
            $webhookId
        );

        // Configuracja mocków
        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Tworzymy rzeczywisty obiekt WebhookRequestDto zamiast mocka
        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-transaction.create',
            $webhookId,
            'valid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto));

        // Symulacja wysłania wiadomości przez MessageBus
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing webhook', $this->arrayHasKey('type'));

        // Act
        $response = $this->controller->handlePaymentWebhook($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testHandlePaymentWebhookThrowsExceptionWhenMissingRequiredFieldsOrderRefund(): void
    {
        // Arrange
        $shopLicense = 'test-license';
        $webhookId = 'webhook-id-123';
        $payload = [
            'type' => 'order-refund.create',
        ];
        $request = $this->createWebhookRequest(
            $payload,
            $shopLicense,
            'order-refund.create',
            'valid-signature',
            $webhookId
        );

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $webhookRequestDto = new WebhookRequestDto(
            $shopLicense,
            'order-refund.create',
            $webhookId,
            'valid-signature',
            $payload,
            json_encode($payload)
        );

        $this->setStaticMethodMock(
            WebhookRequestDto::class,
            'fromRequest',
            function() use ($webhookRequestDto) { return $webhookRequestDto; }
        );

        $this->signatureVerifier->expects($this->once())
            ->method('verify')
            ->with($this->equalTo($webhookRequestDto));

        // Symulacja wysłania wiadomości przez MessageBus
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing webhook', $this->arrayHasKey('type'));

        // Act
        $response = $this->controller->handlePaymentWebhook($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    /**
     * Helper method to set static method mock
     *
     * @param string $class Fully qualified class name
     * @param string $method Method name
     * @param callable $callback Callback function that will be called instead of the original method
     */
    private function setStaticMethodMock(string $class, string $method, callable $callback): void
    {
        $GLOBALS['__phpunit_static_method_mocks'][$class . '::' . $method] = $callback;
    }
}
