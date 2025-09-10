<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ShopVersionUpdateEvent;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles shop version updates
 */
class ShopVersionUpdateSubscriber implements EventSubscriberInterface
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
            ShopVersionUpdateEvent::NAME => 'onShopVersionUpdate',
        ];
    }

    /**
     * Process shop version update
     */
    public function onShopVersionUpdate(ShopVersionUpdateEvent $event): void
    {
        $this->logger->info('Processing shop version update', [
            'shop_id' => $event->getOAuthShop()->getId(),
            'version' => $event->getVersion()
        ]);

        $this->shopPersistenceService->updateApplicationVersion(
            $event->getOAuthShop(),
            $event->getShop()
        );
    }
}
