<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ShopPaymentMethodController;
use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ReflectionMethod;

class ShopPaymentMethodControllerTest extends TestCase
{
    private ShopPaymentMethodController $controller;
    /** @var ShopAppInstallationRepositoryInterface|MockObject */
    private $shopRepository;
    /** @var ShopPaymentMethodRepositoryInterface|MockObject */
    private $paymentMethodRepository;
    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->shopRepository = $this->getMockBuilder(ShopAppInstallationRepositoryInterface::class)
            ->getMock();
        $this->paymentMethodRepository = $this->createMock(ShopPaymentMethodRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new ShopPaymentMethodController(
            $this->shopRepository,
            $this->paymentMethodRepository,
            $this->logger
        );
    }

    /**
     * @dataProvider normalizeShopUrlProvider
     */
    public function testNormalizeShopUrl(?string $input, ?string $expected): void
    {
        // Arrange
        $method = new ReflectionMethod(ShopPaymentMethodController::class, 'normalizeShopUrl');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->controller, $input);

        // Assert
        $this->assertSame($expected, $result);
    }

    public static function normalizeShopUrlProvider(): array
    {
        return [
            'null input' => [null, null],
            'plain domain' => ['example.com', 'example.com'],
            'http protocol' => ['http://example.com', 'example.com'],
            'https protocol' => ['https://example.com', 'example.com'],
            'domain with path' => ['example.com/path', 'example.com/path'],
            'https domain with path' => ['https://example.com/path', 'example.com/path'],
        ];
    }

    public function testCreateCorsResponse(): void
    {
        // Arrange
        $method = new ReflectionMethod(ShopPaymentMethodController::class, 'createCorsResponse');
        $method->setAccessible(true);
        $data = ['key' => 'value'];

        // Act
        $response = $method->invoke($this->controller, $data);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($data), $response->getContent());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
    }

    public function testVerifyWithOptionsMethod(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('OPTIONS');

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(json_encode(['status' => 'ok']), $response->getContent());
    }

    public function testVerifyWithMissingParameters(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('GET');

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testVerifyWithShopNotFound(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('shopUrl', 'example.com');
        $request->query->set('paymentMethodId', '123');

        $this->shopRepository
            ->expects($this->once())
            ->method('findOneByShopUrl')
            ->with('example.com')
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Shop not found by URL', ['shopUrl' => 'example.com']);

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['isSupported']);
        $this->assertEquals('Shop not found', $content['error']);
    }

    public function testVerifyWithPaymentMethodNotFound(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('shopUrl', 'example.com');
        $request->query->set('paymentMethodId', 123);

        $shop = new ShopAppInstallation('shop123', 'example.com', 1, 'auth-code-123');

        $this->shopRepository
            ->expects($this->once())
            ->method('findOneByShopUrl')
            ->with('example.com')
            ->willReturn($shop);

        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('findActiveOneByShopAndPaymentMethodId')
            ->with($shop, 123)
            ->willReturn(null);

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['isSupported']);
        $this->assertEquals('shop123', $content['shopCode']);
    }

    public function testVerifyWithPaymentMethodFound(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('shopUrl', 'example.com');
        $request->query->set('paymentMethodId', 123);

        $shop = new ShopAppInstallation('shop123', 'example.com', 1, 'auth-code-123');
        $paymentMethod = new ShopPaymentMethod($shop, 123);

        $this->shopRepository
            ->expects($this->once())
            ->method('findOneByShopUrl')
            ->with('example.com')
            ->willReturn($shop);

        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('findActiveOneByShopAndPaymentMethodId')
            ->willReturn($paymentMethod);

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['isSupported']);
        $this->assertEquals('shop123', $content['shopCode']);
    }

    public function testVerifyWithJsonpCallback(): void
    {
        // Arrange
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('shopUrl', 'example.com');
        $request->query->set('paymentMethodId', 123);
        $request->query->set('callback', 'myCallback');

        $shop = new ShopAppInstallation('shop123', 'example.com', 1, 'auth-code-123');
        $paymentMethod = new ShopPaymentMethod($shop, 123);

        $this->shopRepository
            ->expects($this->once())
            ->method('findOneByShopUrl')
            ->with('example.com')
            ->willReturn($shop);

        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('findActiveOneByShopAndPaymentMethodId')
            ->willReturn($paymentMethod);

        // Act
        $response = $this->controller->verify($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('myCallback(', $response->getContent());
        $this->assertStringEndsWith(');', $response->getContent());
    }
}
