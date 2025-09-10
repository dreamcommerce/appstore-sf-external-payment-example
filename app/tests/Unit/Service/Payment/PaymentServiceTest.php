<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Payment;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use App\Service\Payment\PaymentService;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Persistence\PaymentMethodPersistenceServiceInterface;
use App\Service\Shop\ShopContextService;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;
    private MockObject $currencyHelper;
    private MockObject $shopContextService;
    private MockObject $paymentMethodPersistenceService;
    private MockObject $shopPaymentMethodRepository;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->currencyHelper = $this->createMock(CurrencyHelper::class);
        $this->shopContextService = $this->createMock(ShopContextService::class);
        $this->paymentMethodPersistenceService = $this->createMock(PaymentMethodPersistenceServiceInterface::class);
        $this->shopPaymentMethodRepository = $this->createMock(ShopPaymentMethodRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new PaymentService(
            $this->currencyHelper,
            $this->shopContextService,
            $this->paymentMethodPersistenceService,
            $this->shopPaymentMethodRepository,
            $this->logger
        );
    }

    public function testCreatePaymentSuccess(): void
    {
        // This test would require mocking the PaymentResource constructor and API calls
        // which is complex due to the external API dependency.
        // For now, we'll focus on testing the service logic that we can properly unit test.
        $this->markTestSkipped('PaymentResource API calls require integration testing approach');
    }

    public function testUpdatePaymentSuccess(): void
    {
        // This test would require mocking the PaymentResource constructor and API calls
        // which is complex due to the external API dependency.
        $this->markTestSkipped('PaymentResource API calls require integration testing approach');
    }

    public function testDeletePaymentSuccess(): void
    {
        // This test would require mocking the PaymentResource constructor and API calls
        // which is complex due to the external API dependency.
        $this->markTestSkipped('PaymentResource API calls require integration testing approach');
    }

    public function testRemoveAllForShopSuccess(): void
    {
        // Arrange
        $shopCode = 'test-shop-code';
        $shopInstallation = $this->createMock(ShopAppInstallation::class);
        
        $paymentMethod1 = $this->createMock(ShopPaymentMethod::class);
        $paymentMethod1->method('getPaymentMethodId')->willReturn(123);
        
        $paymentMethod2 = $this->createMock(ShopPaymentMethod::class);
        $paymentMethod2->method('getPaymentMethodId')->willReturn(456);

        $shopData = [
            'oauthShop' => $this->createMock(OAuthShop::class),
            'shopClient' => $this->createMock(ShopClient::class),
            'shopEntity' => $shopInstallation
        ];

        $this->shopContextService
            ->expects($this->once())
            ->method('getShopAndClient')
            ->with($shopCode)
            ->willReturn($shopData);

        // We can't easily mock the PaymentResource API calls in unit tests,
        // and the repository's findBy method cannot be mocked in this context
        // This test would be better suited as an integration test
        $this->markTestSkipped('PaymentService API calls and repository interactions require integration testing approach');
    }

    public function testRemoveAllForShopWithPartialFailure(): void
    {
        // This test would require mocking PaymentResource API calls and exception handling
        // which is complex due to the external API dependency.
        $this->markTestSkipped('PaymentResource API calls require integration testing approach');
    }

    public function testRemoveAllForShopNotFound(): void
    {
        // Arrange
        $shopCode = 'non-existent-shop';

        $this->shopContextService
            ->expects($this->once())
            ->method('getShopAndClient')
            ->with($shopCode)
            ->willReturn(null);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shop not found for code: non-existent-shop');

        // Act
        $this->service->removeAllForShop($shopCode);
    }

    public function testRemoveAllForShopInstallationNotFound(): void
    {
        // Arrange
        $shopCode = 'test-shop-code';

        $shopData = [
            'oauthShop' => $this->createMock(OAuthShop::class),
            'shopClient' => $this->createMock(ShopClient::class),
            'shopEntity' => null // No shop installation
        ];

        $this->shopContextService
            ->expects($this->once())
            ->method('getShopAndClient')
            ->with($shopCode)
            ->willReturn($shopData);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shop installation not found for code: test-shop-code');

        // Act
        $this->service->removeAllForShop($shopCode);
    }

    public function testGetShopDataOrThrowWithNullShopData(): void
    {
        // Arrange
        $shopCode = 'non-existent-shop';

        $this->shopContextService
            ->expects($this->once())
            ->method('getShopAndClient')
            ->with($shopCode)
            ->willReturn(null);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shop not found for code: non-existent-shop');

        // Act - call any method that uses getShopDataOrThrow
        $this->service->deletePayment($shopCode, 123);
    }

}
