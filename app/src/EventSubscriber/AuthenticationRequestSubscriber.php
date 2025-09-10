<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Authentication\AuthenticationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles authentication requests received through events
 */
class AuthenticationRequestSubscriber implements EventSubscriberInterface
{
    private AuthenticationServiceInterface $authenticationService;
    private LoggerInterface $logger;

    public function __construct(
        AuthenticationServiceInterface $authenticationService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'app.auth.requested' => 'onAuthenticationRequested',
        ];
    }

    /**
     * Process authentication request
     */
    public function onAuthenticationRequested(AppStoreLifecycleEvent $event): void
    {
        $this->logger->info('Processing authentication request', [
            'shop' => $event->shopId,
            'shop_url' => $event->shopUrl
        ]);

        $this->authenticationService->processAuthentication($event);
    }
}
