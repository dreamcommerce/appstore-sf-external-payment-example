<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception\Handler;

use App\Exception\Handler\HttpExceptionHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpExceptionHandlerTest extends TestCase
{
    private LoggerInterface $logger;
    private HttpExceptionHandler $handler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new HttpExceptionHandler($this->logger, 'dev');
    }

    private function createExceptionEvent(HttpExceptionInterface $exception): ExceptionEvent
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

    public function testSupportsHttpException(): void
    {
        // Arrange
        $exception = new NotFoundHttpException('Resource not found');

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertTrue($result);
    }

    public function testDoesNotSupportNonHttpException(): void
    {
        // Arrange
        $exception = new \RuntimeException('Regular exception');

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @dataProvider provideHttpExceptions
     */
    public function testHandleHttpException(HttpExceptionInterface $exception, int $expectedStatusCode): void
    {
        // Arrange
        $event = $this->createExceptionEvent($exception);
        $this->logger->expects($this->once())->method('notice');

        // Act
        $response = $this->handler->handle($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals($exception->getMessage(), $content['message']);
    }

    public static function provideHttpExceptions(): array
    {
        return [
            'not_found' => [
                new NotFoundHttpException('Resource not found'),
                404
            ],
            'bad_request' => [
                new BadRequestHttpException('Bad request format'),
                400
            ],
            'access_denied' => [
                new AccessDeniedHttpException('Access denied to this resource'),
                403
            ]
        ];
    }
}
