<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception\Handler;

use App\Exception\Handler\ValidationExceptionHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationExceptionHandlerTest extends TestCase
{
    private LoggerInterface $logger;
    private ValidationExceptionHandler $handler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ValidationExceptionHandler($this->logger, 'dev');
    }

    private function createExceptionEvent(ValidationFailedException $exception): ExceptionEvent
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

    public function testSupportsValidationFailedException(): void
    {
        // Arrange
        $violations = new ConstraintViolationList();
        $exception = new ValidationFailedException('object', $violations);

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertTrue($result);
    }

    public function testDoesNotSupportOtherExceptions(): void
    {
        // Arrange
        $exception = new \RuntimeException('Some error');

        // Act
        $result = $this->handler->supports($exception);

        // Assert
        $this->assertFalse($result);
    }

    public function testHandleValidationException(): void
    {
        // Arrange
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'This field is required',
                'This field is required',
                [],
                'object',
                'name',
                null
            ),
            new ConstraintViolation(
                'Value must be a positive number',
                'Value must be a positive number',
                [],
                'object',
                'price',
                -5
            )
        ]);

        $exception = new ValidationFailedException('object', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->logger->expects($this->once())->method('notice');

        // Act
        $response = $this->handler->handle($exception, $event);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(2, $content['errors']);
        $this->assertEquals('This field is required', $content['errors']['name']);
        $this->assertEquals('Value must be a positive number', $content['errors']['price']);
    }
}
