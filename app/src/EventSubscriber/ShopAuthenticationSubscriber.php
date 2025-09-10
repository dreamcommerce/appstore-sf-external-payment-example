<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ShopAuthenticatedEvent;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles shop persistence after successful authentication
 */
class ShopAuthenticationSubscriber implements EventSubscriberInterface
{
    private ShopPersistenceServiceInterface $shopPersistenceService;
    private LoggerInterface $logger;

    public function __construct(
        ShopPersistenceServiceInterface $shopPersistenceService,
        LoggerInterface $logger
    ) {
        $this->shopPersistenceService = $shopPersistenceService;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ShopAuthenticatedEvent::NAME => 'onShopAuthenticated',
        ];
    }

    /**
     * Process shop persistence after successful authentication
     */
    public function onShopAuthenticated(ShopAuthenticatedEvent $event): void
    {
        $this->logger->info('Processing shop persistence after authentication', [
            'shop_id' => $event->getShop()->getId()
        ]);

        $this->shopPersistenceService->saveShopAppInstallation(
            $event->getShop(),
            $event->getAuthCode()
        );
    }
}
