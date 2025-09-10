<?php

namespace App\Tests\Integration\Messenger\Middleware;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Messenger\Middleware\ApiExceptionMiddleware;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

class ApiExceptionMiddlewareIntegrationTest extends TestCase
{
    private ApiExceptionClassifier $exceptionClassifier;
    private ApiExceptionMiddleware $middleware;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->exceptionClassifier = new ApiExceptionClassifier();
        $this->logger = new TestLogger();
        $this->middleware = new ApiExceptionMiddleware($this->exceptionClassifier, $this->logger);
    }

    #[DataProvider('temporaryCodesProvider')]
    public function testTemporaryCodesAreClassifiedCorrectly(int $code): void
    {
        // Arrange
        $message = new TestPaymentMessage('shop123');
        $envelope = new Envelope($message);
        $stack = $this->createFailingStackWithException(
            new ApiException("Test error with code $code", $code)
        );
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            $this->assertInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString((string)$code, $e->getMessage());
        }
    }

    public static function temporaryCodesProvider(): array
    {
        return [[429], [500], [502], [503], [504]];
    }

    #[DataProvider('permanentCodesProvider')]
    public function testPermanentCodesAreClassifiedCorrectly(int $code): void
    {
        // Arrange
        $message = new TestPaymentMessage('shop123');
        $envelope = new Envelope($message);
        $stack = $this->createFailingStackWithException(
            new ApiException("Test error with code $code", $code)
        );
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('PaymentApiException was not thrown');
        } catch (PaymentApiException $e) {
            $this->assertInstanceOf(PaymentApiException::class, $e);
            $this->assertNotInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString($code, $e->getMessage());
        }
    }

    public static function permanentCodesProvider(): array
    {
        return [[400], [401], [403], [404], [422]];
    }

    #[DataProvider('temporaryMessagesProvider')]
    public function testTemporaryMessagesAreClassifiedCorrectly(string $errorMessage): void
    {
        // Arrange
        $message = new TestPaymentMessage('shop123');
        $envelope = new Envelope($message);
        $stack = $this->createFailingStackWithException(
            new ApiException($errorMessage, 400)
        );
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('TemporaryPaymentApiException was not thrown');
        } catch (TemporaryPaymentApiException $e) {
            $this->assertInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString($errorMessage, $e->getMessage());
        }
    }

    public static function temporaryMessagesProvider(): array
    {
        return [
            ['Connection timeout'],
            ['Too many requests, try later'],
            ['Please try again in a few minutes'],
            ['Service temporarily unavailable']
        ];
    }

    #[DataProvider('permanentMessagesProvider')]
    public function testPermanentMessagesAreClassifiedCorrectly(string $errorMessage): void
    {
        // Arrange
        $message = new TestPaymentMessage('shop123');
        $envelope = new Envelope($message);
        $stack = $this->createFailingStackWithException(
            new ApiException($errorMessage, 400)
        );
        // Act & Assert
        try {
            $this->middleware->handle($envelope, $stack);
            $this->fail('PaymentApiException was not thrown');
        } catch (PaymentApiException $e) {
            $this->assertInstanceOf(PaymentApiException::class, $e);
            $this->assertNotInstanceOf(TemporaryPaymentApiException::class, $e);
            $this->assertStringContainsString($errorMessage, $e->getMessage());
        }
    }

    public static function permanentMessagesProvider(): array
    {
        return [
            ['Invalid payment data'],
            ['Unauthorized access'],
            ['Payment rejected'],
            ['Resource not found']
        ];
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
 * Test message class to simulate a payment message with shop code
 */
class TestPaymentMessage
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

/**
 * Simple logger implementation for testing
 */
class TestLogger implements LoggerInterface
{
    private array $logs = [];

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }
}
