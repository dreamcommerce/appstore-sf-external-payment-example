<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Domain\Shop\Model\Shop;
use App\Event\ShopVersionUpdateEvent;
use App\EventSubscriber\ShopVersionUpdateSubscriber;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShopVersionUpdateSubscriberTest extends TestCase
{
    private ShopVersionUpdateSubscriber $subscriber;
    private MockObject $shopPersistenceService;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->shopPersistenceService = $this->createMock(ShopPersistenceServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new ShopVersionUpdateSubscriber(
            $this->shopPersistenceService,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        // Arrange
        $expectedEvents = [
            ShopVersionUpdateEvent::NAME => 'onShopVersionUpdate',
        ];

        // Act
        $subscribedEvents = ShopVersionUpdateSubscriber::getSubscribedEvents();

        // Assert
        $this->assertEquals($expectedEvents, $subscribedEvents);
    }

    public function testOnShopVersionUpdate(): void
    {
        // Arrange
        $oAuthShop = $this->createMock(OAuthShop::class);
        $shopModel = $this->createMock(Shop::class);
        $version = 2;

        $oAuthShop->expects($this->once())
            ->method('getId')
            ->willReturn('test-shop-id');

        $event = new ShopVersionUpdateEvent($oAuthShop, $shopModel, $version);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Processing shop version update',
                ['shop_id' => 'test-shop-id', 'version' => 2]
            );

        $this->shopPersistenceService->expects($this->once())
            ->method('updateApplicationVersion')
            ->with($oAuthShop, $shopModel);

        // Act
        $this->subscriber->onShopVersionUpdate($event);

        // Assert
        // Assertions are handled via mock expectations
    }
}
