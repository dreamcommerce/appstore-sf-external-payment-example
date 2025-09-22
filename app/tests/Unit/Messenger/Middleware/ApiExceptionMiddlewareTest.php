<?php

namespace App\Tests\Unit\Messenger\Middleware;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Messenger\Middleware\ApiExceptionMiddleware;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

class ApiExceptionMiddlewareTest extends TestCase
{
    private ApiExceptionClassifier $exceptionClassifier;
    private LoggerInterface $logger;
    private ApiExceptionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->exceptionClassifier = $this->createMock(ApiExceptionClassifier::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->middleware = new ApiExceptionMiddleware($this->exceptionClassifier, $this->logger);
    }

    public function testItHandlesTemporaryApiExceptionCorrectly(): void
    {
        // Arrange
        $envelope = new Envelope(new TestMessage('shop123'));
        $stack = $this->createFailingStackWithException(
            new ApiException('Service temporarily unavailable', 503)
        );
        $this->exceptionClassifier
            ->method('isRecoverableError')
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('error');
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            $this->assertInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString('Service temporarily unavailable', $e->getMessage());
        }
    }

    public function testItHandlesPermanentApiExceptionCorrectly(): void
    {
        // Arrange
        $envelope = new Envelope(new TestMessage('shop123'));
        $stack = $this->createFailingStackWithException(
            new ApiException('Invalid payment data', 400)
        );
        $this->exceptionClassifier
            ->method('isRecoverableError')
            ->willReturn(false);
        $this->logger
            ->expects($this->once())
            ->method('error');
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('PaymentApiException was not thrown');
        } catch (PaymentApiException $e) {
            $this->assertInstanceOf(PaymentApiException::class, $e);
            $this->assertNotInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString('Invalid payment data', $e->getMessage());
        }
    }

    public function testItHandlesNestedApiExceptionInHandlerFailedException(): void
    {
        // Arrange
        $envelope = new Envelope(new TestMessage('shop123'));
        $apiException = new ApiException('Gateway timeout', 504);
        $handlerException = new HandlerFailedException(
            $envelope,
            [$apiException]
        );
        $stack = $this->createFailingStackWithException($handlerException);
        $this->exceptionClassifier
            ->method('isRecoverableError')
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('error');
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            $this->assertInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString('Gateway timeout', $e->getMessage());
        }
    }

    public function testItExtractsShopCodeFromMessage(): void
    {
        // Arrange
        $shopCode = 'test-shop-123';
        $envelope = new Envelope(new TestMessage($shopCode));
        $stack = $this->createFailingStackWithException(
            new ApiException('Service unavailable', 503)
        );
        $this->exceptionClassifier
            ->method('isRecoverableError')
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->anything(),
                $this->callback(function (array $context) {
                    // Assert
                    return isset($context['message_class'], $context['error_message'], $context['error_code'])
                        && $context['message_class'] === TestMessage::class
                        && $context['error_message'] === 'Service unavailable'
                        && $context['error_code'] === 503;
                })
            );
        // Act & Assert
        $this->expectException(TemporaryPaymentApiException::class);
        $this->middleware->handle($envelope, $stack);
    }

    /**
     * Creates a middleware stack that throws an exception
     */
    private function createFailingStackWithException(\Throwable $exception): StackMiddleware
    {
        $stack = $this->createMock(StackMiddleware::class);
        $stack->method('next')->willReturnSelf();
        $stack->method('handle')->willThrowException($exception);

        return $stack;
    }
}

/**
 * Test message class to simulate a message with shop code
 */
class TestMessage
{
    private string $shopCode;

    public function __construct(string $shopCode)
    {
        $this->shopCode = $shopCode;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }
}
