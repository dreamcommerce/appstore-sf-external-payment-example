<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Persistence;

use App\Domain\Shop\Model\Shop;
use App\Entity\ShopAppInstallation;
use App\Factory\ShopAppInstallationFactory;
use App\Repository\ShopAppInstallationRepository;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Persistence\ShopPersistenceService;
use App\Service\Token\TokenManagerInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use DreamCommerce\Component\ShopAppstore\Model\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShopPersistenceServiceTest extends TestCase
{
    private ShopPersistenceService $service;
    private MockObject $logger;
    private MockObject $shopAppInstallationRepository;
    private MockObject $tokenManager;
    private MockObject $shopAppInstallationFactory;
    private MockObject $paymentService;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->shopAppInstallationRepository = $this->createMock(ShopAppInstallationRepository::class);
        $this->tokenManager = $this->createMock(TokenManagerInterface::class);
        $this->shopAppInstallationFactory = $this->createMock(ShopAppInstallationFactory::class);
        $this->paymentService = $this->createMock(PaymentServiceInterface::class);

        $this->service = new ShopPersistenceService(
            $this->logger,
            $this->shopAppInstallationRepository,
            $this->tokenManager,
            $this->shopAppInstallationFactory,
            $this->paymentService
        );
    }

    public function testSaveShopAppInstallationWithoutToken(): void
    {
        // Arrange
        $oauthShop = $this->createMock(OAuthShop::class);
        $oauthShop->method('getToken')->willReturn(null);
        $oauthShop->method('getId')->willReturn('test-shop-id');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Cannot save shop: Token is missing', [
                'shop_id' => 'test-shop-id'
            ]);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot save shop: Token is missing for shop_id: test-shop-id');

        // Act
        $this->service->saveShopAppInstallation($oauthShop, 'auth-code');
    }

    public function testSaveShopAppInstallationSuccess(): void
    {
        // Arrange
        $token = $this->createMock(Token::class);

        $oauthShop = $this->createMock(OAuthShop::class);
        $oauthShop->method('getToken')->willReturn($token);
        $oauthShop->method('getId')->willReturn('test-shop-id');
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('__toString')->willReturn('https://test-shop.com');
        $oauthShop->method('getUri')->willReturn($uri);

        $shopInstallation = $this->createMock(ShopAppInstallation::class);
        $shopToken = $this->createMock(\App\Entity\ShopAppToken::class);
        $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh'];

        $this->shopAppInstallationFactory
            ->expects($this->once())
            ->method('fromOAuthShop')
            ->with($oauthShop, 'auth-code')
            ->willReturn($shopInstallation);

        $this->tokenManager
            ->expects($this->once())
            ->method('prepareTokenResponse')
            ->with($oauthShop)
            ->willReturn($tokenData);

        $this->shopAppInstallationFactory
            ->expects($this->once())
            ->method('createToken')
            ->with($shopInstallation, $tokenData)
            ->willReturn($shopToken);

        $shopInstallation
            ->expects($this->once())
            ->method('addToken')
            ->with($shopToken);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('save')
            ->with($shopInstallation);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Shop installation data saved successfully', [
                'shop_id' => 'test-shop-id',
                'shop_url' => $uri
            ]);

        // Act
        $this->service->saveShopAppInstallation($oauthShop, 'auth-code');
    }

    public function testSaveShopAppInstallationWithException(): void
    {
        // Arrange
        $token = $this->createMock(Token::class);
        $oauthShop = $this->createMock(OAuthShop::class);
        $oauthShop->method('getToken')->willReturn($token);
        $oauthShop->method('getId')->willReturn('test-shop-id');
        $exception = new \RuntimeException('Factory error');

        $this->shopAppInstallationFactory
            ->expects($this->once())
            ->method('fromOAuthShop')
            ->with($oauthShop, 'auth-code')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Error while saving shop data', [
                'shop_id' => 'test-shop-id',
                'error' => 'Factory error'
            ]);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Factory error');

        // Act
        $this->service->saveShopAppInstallation($oauthShop, 'auth-code');
    }

    public function testUpdateApplicationVersionSuccess(): void
    {
        // Arrange
        $oauthShop = $this->createMock(OAuthShop::class);
        $oauthShop->method('getVersion')->willReturn(2);
        $oauthShop->method('getId')->willReturn('test-shop-id');

        $shop = $this->createMock(Shop::class);
        $shop->method('getVersion')->willReturn(3);

        $shopInstallation = $this->createMock(ShopAppInstallation::class);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => 'test-shop-id'])
            ->willReturn($shopInstallation);

        $shopInstallation
            ->expects($this->once())
            ->method('setApplicationVersion')
            ->with(3);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('save')
            ->with($shopInstallation);

        // Act
        $this->service->updateApplicationVersion($oauthShop, $shop);
    }

    public function testUpdateApplicationVersionShopNotFound(): void
    {
        // Arrange
        $oauthShop = $this->createMock(OAuthShop::class);
        $oauthShop->method('getId')->willReturn('non-existent-shop-id');
        $shop = $this->createMock(Shop::class);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => 'non-existent-shop-id'])
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Cannot update application version: Shop not found', [
                'shop_id' => 'non-existent-shop-id'
            ]);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot update application version: Shop not found for shop_id: non-existent-shop-id');

        // Act
        $this->service->updateApplicationVersion($oauthShop, $shop);
    }

    public function testUninstallShopSuccess(): void
    {
        // Arrange
        $shopId = 'test-shop-id';
        $shopUrl = 'https://test-shop.com';
        $shopInstallation = $this->createMock(ShopAppInstallation::class);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => $shopId])
            ->willReturn($shopInstallation);

        $this->paymentService
            ->expects($this->once())
            ->method('removeAllForShop')
            ->with($shopId);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('remove')
            ->with($shopInstallation);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Application uninstalled successfully', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl
            ]);

        // Act
        $this->service->uninstallShop($shopId, $shopUrl);
    }

    public function testUninstallShopNotFound(): void
    {
        // Arrange
        $shopId = 'non-existent-shop-id';
        $shopUrl = 'https://non-existent-shop.com';

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => $shopId])
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Shop installation not found during uninstall', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl
            ]);

        $this->paymentService
            ->expects($this->never())
            ->method('removeAllForShop');

        $this->shopAppInstallationRepository
            ->expects($this->never())
            ->method('remove');

        // Act
        $this->service->uninstallShop($shopId, $shopUrl);
    }

    public function testUninstallShopPaymentServiceThrowsException(): void
    {
        // Arrange
        $shopId = 'test-shop-id';
        $shopUrl = 'https://test-shop.com';
        $shopInstallation = $this->createMock(ShopAppInstallation::class);
        $exception = new \RuntimeException('Payment service error');

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => $shopId])
            ->willReturn($shopInstallation);

        $this->paymentService
            ->expects($this->once())
            ->method('removeAllForShop')
            ->with($shopId)
            ->willThrowException($exception);

        $this->shopAppInstallationRepository
            ->expects($this->never())
            ->method('remove');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment service error');

        // Act
        $this->service->uninstallShop($shopId, $shopUrl);
    }

    public function testUninstallShopRepositoryThrowsException(): void
    {
        // Arrange
        $shopId = 'test-shop-id';
        $shopUrl = 'https://test-shop.com';
        $shopInstallation = $this->createMock(ShopAppInstallation::class);
        $exception = new \RuntimeException('Repository error');

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['shop' => $shopId])
            ->willReturn($shopInstallation);

        $this->paymentService
            ->expects($this->once())
            ->method('removeAllForShop')
            ->with($shopId);

        $this->shopAppInstallationRepository
            ->expects($this->once())
            ->method('remove')
            ->with($shopInstallation)
            ->willThrowException($exception);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Repository error');

        // Act
        $this->service->uninstallShop($shopId, $shopUrl);
    }
}
