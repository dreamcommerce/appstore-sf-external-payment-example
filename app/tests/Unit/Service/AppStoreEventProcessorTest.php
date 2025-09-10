<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Message\CreatePaymentMessage;
use App\Service\AppStoreEventProcessor;
use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\Event\AppStoreLifecycleTrial;
use App\Service\OAuth\OAuthService;
use App\Service\Persistence\ShopPersistenceService;
use App\ValueObject\PaymentData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class AppStoreEventProcessorTest extends TestCase
{
    private AppStoreEventProcessor $processor;
    private MockObject $logger;
    private MockObject $oauthService;
    private MockObject $messageBus;
    private MockObject $shopPersistenceService;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->oauthService = $this->createMock(OAuthService::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->shopPersistenceService = $this->createMock(ShopPersistenceService::class);

        $this->processor = new AppStoreEventProcessor(
            $this->logger,
            $this->oauthService,
            $this->messageBus,
            $this->shopPersistenceService
        );
    }

    public function testHandleInstallEvent(): void
    {
        // Arrange
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::INSTALL,
            applicationCode: 'test-app',
            version: 1,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->oauthService
            ->expects($this->once())
            ->method('authenticate')
            ->with($event);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($event) {
                return $message instanceof CreatePaymentMessage 
                    && $message->getShopCode() === $event->shopId
                    && $message->getPaymentData() instanceof PaymentData;
            }))
            ->willReturn(new Envelope($this->createMock(CreatePaymentMessage::class)));

        // Act
        $this->processor->handleEvent($event);
    }

    public function testHandleUpgradeEventSuccess(): void
    {
        // Arrange
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::UPGRADE,
            applicationCode: 'test-app',
            version: 2,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->oauthService
            ->expects($this->once())
            ->method('authenticate')
            ->with($event);

        $this->oauthService
            ->expects($this->once())
            ->method('updateApplicationVersion')
            ->with($event);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Application version update during upgrade', [
                'shop_id' => $event->shopId,
                'shop_url' => $event->shopUrl,
                'version' => $event->version,
                'success' => true
            ]);

        // Act
        $this->processor->handleEvent($event);
    }

    public function testHandleUninstallEvent(): void
    {
        // Arrange
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::UNINSTALL,
            applicationCode: 'test-app',
            version: 1,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Uninstalling the application');

        $this->shopPersistenceService
            ->expects($this->once())
            ->method('uninstallShop')
            ->with($event->shopId, $event->shopUrl);

        // Act
        $this->processor->handleEvent($event);
    }

    public function testHandleMultipleEventsInSequence(): void
    {
        // Test that the processor can handle multiple events correctly

        // 1. Install Event
        $installEvent = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::INSTALL,
            applicationCode: 'test-app',
            version: 1,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->oauthService
            ->expects($this->exactly(2)) // Will be called for both install and upgrade
            ->method('authenticate');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope($this->createMock(CreatePaymentMessage::class)));

        // 2. Upgrade Event
        $upgradeEvent = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::UPGRADE,
            applicationCode: 'test-app',
            version: 2,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->oauthService
            ->expects($this->once())
            ->method('updateApplicationVersion');

        // 3. Uninstall Event
        $uninstallEvent = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::UNINSTALL,
            applicationCode: 'test-app',
            version: 2,
            authCode: 'auth-code-123',
            shopId: 'test-shop-123',
            shopUrl: 'https://test-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-123'
        );

        $this->shopPersistenceService
            ->expects($this->once())
            ->method('uninstallShop');

        // Act - Process events in sequence
        $this->processor->handleEvent($installEvent);
        $this->processor->handleEvent($upgradeEvent);
        $this->processor->handleEvent($uninstallEvent);
    }

    public function testInstallEventCreatesCorrectPaymentMessage(): void
    {
        // Arrange
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::INSTALL,
            applicationCode: 'test-app',
            version: 1,
            authCode: 'auth-code-456',
            shopId: 'test-shop-456',
            shopUrl: 'https://another-shop.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-456'
        );

        $capturedMessage = null;

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use (&$capturedMessage) {
                $capturedMessage = $message;
                return $message instanceof CreatePaymentMessage;
            }))
            ->willReturn(new Envelope($this->createMock(CreatePaymentMessage::class)));

        // Act
        $this->processor->handleEvent($event);

        // Assert
        $this->assertInstanceOf(CreatePaymentMessage::class, $capturedMessage);
        $this->assertEquals('test-shop-456', $capturedMessage->getShopCode());
        $this->assertInstanceOf(PaymentData::class, $capturedMessage->getPaymentData());
        
        $paymentData = $capturedMessage->getPaymentData();
        $this->assertStringContainsString('External Payment', $paymentData->getTitle());
        $this->assertStringContainsString('from example App', $paymentData->getTitle());
        $this->assertEquals('External payment created during installation', $paymentData->getDescription());
    }

    public function testUninstallEventCallsCorrectParameters(): void
    {
        // Arrange
        $shopId = 'specific-shop-789';
        $shopUrl = 'https://specific-shop.example.com';
        
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::UNINSTALL,
            applicationCode: 'test-app',
            version: 3,
            authCode: 'auth-code-789',
            shopId: $shopId,
            shopUrl: $shopUrl,
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash-789'
        );

        $this->shopPersistenceService
            ->expects($this->once())
            ->method('uninstallShop')
            ->with(
                $this->equalTo($shopId),
                $this->equalTo($shopUrl)
            );

        // Act
        $this->processor->handleEvent($event);
    }
}
