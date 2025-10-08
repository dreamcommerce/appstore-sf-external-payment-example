<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationRequestSubscriber;
use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\Event\AppStoreLifecycleTrial;
use App\Service\OAuth\Authentication\AuthenticationServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AuthenticationRequestSubscriberTest extends TestCase
{
    private AuthenticationRequestSubscriber $subscriber;
    private MockObject $authenticationService;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new AuthenticationRequestSubscriber(
            $this->authenticationService,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        // Arrange
        $expectedEvents = [
            'app.auth.requested' => 'onAuthenticationRequested',
        ];

        // Act
        $subscribedEvents = AuthenticationRequestSubscriber::getSubscribedEvents();

        // Assert
        $this->assertEquals($expectedEvents, $subscribedEvents);
    }

    public function testOnAuthenticationRequested(): void
    {
        // Arrange
        $event = new AppStoreLifecycleEvent(
            action: AppStoreLifecycleAction::INSTALL,
            applicationCode: 'test-app',
            version: 1,
            authCode: 'test-auth-code',
            shopId: 'test-shop-id',
            shopUrl: 'https://test-shop.example.com',
            trial: AppStoreLifecycleTrial::OFF,
            hash: 'test-hash'
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Processing authentication request',
                ['shop' => 'test-shop-id', 'shop_url' => 'https://test-shop.example.com']
            );

        $this->authenticationService->expects($this->once())
            ->method('processAuthentication')
            ->with($event);

        // Act
        $this->subscriber->onAuthenticationRequested($event);

        // Assert
        // Assertions are handled via mock expectations
    }
}
