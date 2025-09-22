<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception\Handler;

use App\Exception\Handler\ExceptionHandlerInterface;
use App\Exception\Handler\ExceptionHandlerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionHandlerRegistryTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    public function testHandlerSelectionOrderIsPreserved(): void
    {
        // Arrange
        $exception = new \RuntimeException('Test exception');
        $event = $this->createExceptionEvent($exception);

        $firstHandler = $this->createMock(ExceptionHandlerInterface::class);
        $firstHandler->method('supports')->willReturn(true);
        $firstHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new JsonResponse(['success' => false, 'message' => 'First handler']));

        $secondHandler = $this->createMock(ExceptionHandlerInterface::class);
        $secondHandler->method('supports')->willReturn(true);
        $secondHandler->expects($this->never())->method('handle');

        $registry = new ExceptionHandlerRegistry([$firstHandler, $secondHandler], $this->logger);

        // Act
        $response = $registry->handleException($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('First handler', $content['message']);
    }

    public function testFallbackToSecondHandlerWhenFirstDoesNotSupport(): void
    {
        // Arrange
        $exception = new \RuntimeException('Test exception');
        $event = $this->createExceptionEvent($exception);

        $firstHandler = $this->createMock(ExceptionHandlerInterface::class);
        $firstHandler->method('supports')->willReturn(false);
        $firstHandler->expects($this->never())->method('handle');

        $secondHandler = $this->createMock(ExceptionHandlerInterface::class);
        $secondHandler->method('supports')->willReturn(true);
        $secondHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new JsonResponse(['success' => false, 'message' => 'Second handler']));

        $registry = new ExceptionHandlerRegistry([$firstHandler, $secondHandler], $this->logger);

        // Act
        $response = $registry->handleException($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Second handler', $content['message']);
    }

    public function testFallbackToDefaultResponseWhenNoHandlerSupports(): void
    {
        // Arrange
        $exception = new \RuntimeException('Test exception');
        $event = $this->createExceptionEvent($exception);

        $handler = $this->createMock(ExceptionHandlerInterface::class);
        $handler->method('supports')->willReturn(false);
        $handler->expects($this->never())->method('handle');

        $this->logger->expects($this->once())->method('warning');

        $registry = new ExceptionHandlerRegistry([$handler], $this->logger);

        // Act
        $response = $registry->handleException($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('An unexpected error occurred', $content['message']);
        $this->assertFalse($content['success']);
    }
}
