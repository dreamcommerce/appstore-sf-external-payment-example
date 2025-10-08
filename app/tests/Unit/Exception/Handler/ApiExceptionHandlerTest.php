<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception\Handler;

use App\Exception\Handler\ApiExceptionHandler;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionHandlerTest extends TestCase
{
    private ApiExceptionClassifier $exceptionClassifier;
    private LoggerInterface $logger;
    private ApiExceptionHandler $handler;
    private ApiExceptionHandler $prodHandler;

    protected function setUp(): void
    {
        $this->exceptionClassifier = $this->createMock(ApiExceptionClassifier::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ApiExceptionHandler(
            $this->logger,
            $this->exceptionClassifier,
            'dev'
        );

        $this->prodHandler = new ApiExceptionHandler(
            $this->logger,
            $this->exceptionClassifier,
            'prod'
        );
    }

    private function createExceptionEvent(ApiException $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->attributes->set('shopCode', 'test-shop');

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    public function testSupportsApiException(): void
    {
        // Arrange
        $exception = new ApiException('API error', 500);

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertTrue($result);
    }

    public function testDoesNotSupportNonApiException(): void
    {
        // Arrange
        $exception = new \RuntimeException('Regular exception');

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertFalse($result);
    }

    public function testHandlesApiException(): void
    {
        // Arrange
        $exception = new ApiException('API error message', 400);
        $event = $this->createExceptionEvent($exception);

        $this->logger->expects($this->once())->method('error');

        // Act
        $response = $this->handler->handle($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertStringContainsString('API error message', $content['message']);
    }

    public function testMasksDetailedErrorsInProductionEnvironment(): void
    {
        // Arrange
        $exception = new ApiException('Sensitive error details', 400);
        $event = $this->createExceptionEvent($exception);

        // Act
        $response = $this->prodHandler->handle($exception, $event);

        // Assert
        $content = json_decode($response->getContent(), true);
        $this->assertStringNotContainsString('Sensitive error details', $content['message']);
        $this->assertStringContainsString('An error occurred', $content['message']);
    }
}
