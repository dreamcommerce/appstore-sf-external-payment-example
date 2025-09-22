<?php

namespace App\Tests\Unit\Messenger\Middleware;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Messenger\Middleware\ApiExceptionMiddleware;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function itHandlesTemporaryApiExceptionCorrectly(): void
    {
        //Given
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
        //When
        try {
            $this->middleware->handle($envelope, $stack);
            //Then
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            //Then
            $this->assertStringContainsString('Service temporarily unavailable', $e->getMessage());
        }
    }

    #[Test]
    public function itHandlesPermanentApiExceptionCorrectly(): void
    {
        //Given
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
        //When
        try {
            $this->middleware->handle($envelope, $stack);
            //Then
            $this->fail('PaymentApiException was not thrown');
        } catch (PaymentApiException $e) {
            //Then
            $this->assertStringContainsString('Invalid payment data', $e->getMessage());
        }
    }

    #[Test]
    public function itHandlesNestedApiExceptionInHandlerFailedException(): void
    {
        //Given
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
        //When
        try {
            $this->middleware->handle($envelope, $stack);
            //Then
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            //Then
            $this->assertStringContainsString('Gateway timeout', $e->getMessage());
        }
    }

    #[Test]
    public function itExtractsShopCodeFromMessage(): void
    {
        //Given
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
                    //Then
                    return isset($context['message_type'], $context['message'], $context['code'])
                        && $context['message_type'] === TestMessage::class
                        && $context['message'] === 'Service unavailable'
                        && $context['code'] === 503;
                })
            );
        //When
        $this->expectException(TemporaryPaymentApiException::class);
        $this->middleware->handle($envelope, $stack);
    }

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
