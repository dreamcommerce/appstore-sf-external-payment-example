<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Event\ShopAuthenticatedEvent;
use App\EventSubscriber\ShopAuthenticationSubscriber;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShopAuthenticationSubscriberTest extends TestCase
{
    private ShopAuthenticationSubscriber $subscriber;
    private MockObject $shopPersistenceService;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->shopPersistenceService = $this->createMock(ShopPersistenceServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new ShopAuthenticationSubscriber(
            $this->shopPersistenceService,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        // Arrange
        $expectedEvents = [
            ShopAuthenticatedEvent::NAME => 'onShopAuthenticated',
        ];

        // Act
        $subscribedEvents = ShopAuthenticationSubscriber::getSubscribedEvents();

        // Assert
        $this->assertEquals($expectedEvents, $subscribedEvents);
    }

    public function testOnShopAuthenticated(): void
    {
        // Arrange
        $shop = $this->createMock(OAuthShop::class);
        $authCode = 'test-auth-code-123';

        $shop->expects($this->once())
            ->method('getId')
            ->willReturn('test-shop-id');

        $event = new ShopAuthenticatedEvent($shop, $authCode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Processing shop persistence after authentication',
                ['shop_id' => 'test-shop-id']
            );

        $this->shopPersistenceService->expects($this->once())
            ->method('saveShopAppInstallation')
            ->with($shop, $authCode);

        // Act
        $this->subscriber->onShopAuthenticated($event);

        // Assert
        // Assertions are handled via mock expectations
    }
}
