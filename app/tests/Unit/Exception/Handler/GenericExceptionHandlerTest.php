<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception\Handler;

use App\Exception\Handler\GenericExceptionHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class GenericExceptionHandlerTest extends TestCase
{
    private LoggerInterface $logger;
    private GenericExceptionHandler $devHandler;
    private GenericExceptionHandler $prodHandler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->devHandler = new GenericExceptionHandler($this->logger, 'dev');
        $this->prodHandler = new GenericExceptionHandler($this->logger, 'prod');
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

    public function testSupportsAllExceptions(): void
    {
        // Arrange
        $exceptions = [
            new \RuntimeException('Runtime exception'),
            new \LogicException('Logic exception'),
            new \InvalidArgumentException('Invalid argument'),
            new \Exception('General exception')
        ];

        // Act & Assert
        foreach ($exceptions as $exception) {
            $this->assertTrue($this->devHandler->supports($exception));
        }
    }

    public function testHandleExceptionInDevEnvironment(): void
    {
        // Arrange
        $errorMessage = 'This is a detailed error message';
        $exception = new \RuntimeException($errorMessage);
        $event = $this->createExceptionEvent($exception);

        $this->logger->expects($this->once())->method('error');

        // Act
        $response = $this->devHandler->handle($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals($errorMessage, $content['message']);
    }

    public function testHandleExceptionInProdEnvironment(): void
    {
        // Arrange
        $exception = new \RuntimeException('This is a sensitive error message that should not be exposed');
        $event = $this->createExceptionEvent($exception);

        $this->logger->expects($this->once())->method('error');

        // Act
        $response = $this->prodHandler->handle($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('An unexpected error occurred', $content['message']);
        $this->assertStringNotContainsString('sensitive', $content['message']);
    }
}
