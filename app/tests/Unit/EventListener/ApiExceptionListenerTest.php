<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use App\Exception\Handler\ExceptionHandlerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionListenerTest extends TestCase
{
    private ExceptionHandlerRegistry $handlerRegistry;
    private LoggerInterface $logger;
    private ApiExceptionListener $listener;

    protected function setUp(): void
    {
        $this->handlerRegistry = $this->createMock(ExceptionHandlerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ApiExceptionListener($this->handlerRegistry, $this->logger);
    }

    public function testHandlesApiRequests(): void
    {
        // Arrange
        $exception = new \RuntimeException('Test exception');

        $testCases = [
            'api_path' => '/api/payments',
            'app_store_path' => '/app-store/configure',
            'json_accept_header' => '/some/path',
            'json_request_format' => '/another/path',
        ];

        foreach ($testCases as $caseName => $path) {
            $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

            if ($caseName === 'json_accept_header') {
                $request->headers->set('Accept', 'application/json');
            } elseif ($caseName === 'json_request_format') {
                $request->setRequestFormat('json');
            }

            $kernel = $this->createMock(HttpKernelInterface::class);
            $event = new ExceptionEvent(
                $kernel,
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $exception
            );

            $expectedResponse = new JsonResponse(['success' => false, 'message' => 'Error']);

            $this->handlerRegistry->expects($this->once())
                ->method('handleException')
                ->with($exception, $event)
                ->willReturn($expectedResponse);

            $this->logger->expects($this->once())
                ->method('error')
                ->with('Exception caught', $this->anything());

            // Act
            $this->listener->__invoke($event);

            // Assert
            $this->assertSame($expectedResponse, $event->getResponse());

            $this->setUp();
        }
    }

    public function testIgnoresNonApiRequests(): void
    {
        // Arrange
        $exception = new \RuntimeException('Test exception');
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/regular/web/page']);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->handlerRegistry->expects($this->never())
            ->method('handleException');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception caught', $this->anything());

        // Act
        $this->listener->__invoke($event);

        // Assert
        $this->assertNull($event->getResponse());
    }
}
