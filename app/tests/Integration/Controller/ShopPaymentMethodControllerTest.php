<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration test for ShopPaymentMethodController
 *
 * These tests verify that the controller properly handles different request scenarios
 * using mocked dependencies to avoid database connection issues.
 */
final class ShopPaymentMethodControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Replace repositories and other dependencies with mocks to avoid database connection
        $this->mockDependencies();
    }

    /**
     * Sets up mock dependencies for the controller to avoid database connection
     */
    private function mockDependencies(): void
    {
        $container = $this->client->getContainer();
        
        $testShop = new ShopAppInstallation(
            'test-shop-integration',
            'integration-test.example.com',
            1,
            'test-auth-code-123'
        );

        $testPaymentMethod = new ShopPaymentMethod(
            $testShop,
            123
        );

        $shopRepository = $this->getMockBuilder(ShopAppInstallationRepositoryInterface::class)
            ->getMock();
            
        $shopRepository->method('findOneByShopUrl')
            ->willReturnCallback(function($shopUrl) use ($testShop) {
                if ($shopUrl === 'integration-test.example.com') {
                    return $testShop;
                }
                return null;
            });

        $paymentMethodRepository = new class($testShop, $testPaymentMethod) implements ShopPaymentMethodRepositoryInterface {
            private $testShop;
            private $testPaymentMethod;
            
            public function __construct($testShop, $testPaymentMethod) {
                $this->testShop = $testShop;
                $this->testPaymentMethod = $testPaymentMethod;
            }
            
            public function save(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void {}
            public function remove(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void {}
            
            public function findActiveOneByShopAndPaymentMethodId(ShopAppInstallation $shop, int $paymentMethodId): ?ShopPaymentMethod {
                if ($shop === $this->testShop && $paymentMethodId === 123) {
                    return $this->testPaymentMethod;
                }
                return null;
            }
        };

        $container->set(ShopAppInstallationRepositoryInterface::class, $shopRepository);
        $container->set(ShopPaymentMethodRepositoryInterface::class, $paymentMethodRepository);
    }

    /**
     * Tests the OPTIONS request handling for CORS preflight
     */
    public function testVerifyEndpointWithOptions(): void
    {
        // Arrange & Act
        $this->client->request(
            'OPTIONS',
            '/api/shop/payment-methods/verify'
        );

        // Assert
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('*', $this->client->getResponse()->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $this->client->getResponse()->headers->get('Access-Control-Allow-Methods'));
    }

    /**
     * Tests error handling when required parameters are missing
     */
    public function testVerifyEndpointWithMissingParameters(): void
    {
        // Arrange & Act
        $this->client->request(
            'GET',
            '/api/shop/payment-methods/verify'
        );

        // Assert
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    /**
     * Tests handling when shop is not found
     */
    public function testVerifyEndpointWithNonExistentShop(): void
    {
        // Arrange & Act
        $this->client->request(
            'GET',
            '/api/shop/payment-methods/verify?shopUrl=nonexistent.example.com&paymentMethodId=123'
        );

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['isSupported']);
        $this->assertEquals('Shop not found', $responseData['error']);
    }

    /**
     * Tests handling when shop exists but payment method doesn't
     */
    public function testVerifyEndpointWithExistingShopButNonExistentPaymentMethod(): void
    {
        // Arrange & Act
        $this->client->request(
            'GET',
            '/api/shop/payment-methods/verify?shopUrl=integration-test.example.com&paymentMethodId=999'
        );

        // Assert
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['isSupported']);
        $this->assertEquals('test-shop-integration', $responseData['shopCode']);
    }

    /**
     * Tests successful verification when both shop and payment method exist
     */
    public function testVerifyEndpointWithExistingShopAndPaymentMethod(): void
    {
        // Arrange & Act
        $this->client->request(
            'GET',
            '/api/shop/payment-methods/verify?shopUrl=integration-test.example.com&paymentMethodId=123'
        );

        // Assert
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['isSupported']);
        $this->assertEquals('test-shop-integration', $responseData['shopCode']);
    }

    /**
     * Tests JSONP response format when callback parameter is provided
     */
    public function testVerifyEndpointWithJsonpCallback(): void
    {
        // Arrange & Act
        $this->client->request(
            'GET',
            '/api/shop/payment-methods/verify?shopUrl=integration-test.example.com&paymentMethodId=123&callback=testCallback'
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/javascript', $response->headers->get('Content-Type'));
        
        $content = $response->getContent();
        $this->assertIsString($content, 'Response content should be a string');
        
        $this->assertStringStartsWith('testCallback(', $content, 'Response should start with the callback function');
        $this->assertStringEndsWith(');', $content, 'Response should end with closing parenthesis and semicolon');
        
        $jsonStart = strpos($content, '(') + 1;
        $jsonEnd = strrpos($content, ')');
        $json = trim(substr($content, $jsonStart, $jsonEnd - $jsonStart));

        $responseData = json_decode($json, true);
        
        $this->assertNotNull($responseData, 'Failed to decode JSON: ' . json_last_error_msg());
        $this->assertIsArray($responseData, 'Decoded response should be an array');
        
        $this->assertArrayHasKey('isSupported', $responseData, 'Response should contain isSupported key');
        $this->assertArrayHasKey('shopCode', $responseData, 'Response should contain shopCode key');
        
        // Assert the values
        $this->assertTrue($responseData['isSupported'], 'Payment method should be supported');
        $this->assertEquals('test-shop-integration', $responseData['shopCode'], 'Shop code should match');
    }

    /**
     * Tests POST method with JSON body
     */
    public function testVerifyEndpointWithPostMethod(): void
    {
        // Arrange
        $data = [
            'shopUrl' => 'integration-test.example.com',
            'paymentMethodId' => 123
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/shop/payment-methods/verify',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['isSupported']);
        $this->assertEquals('test-shop-integration', $responseData['shopCode']);
    }
}
