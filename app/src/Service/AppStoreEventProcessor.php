<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\PaymentDataFactoryInterface;
use App\Message\CreatePaymentMessage;
use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\OAuthService;
use App\Service\Persistence\ShopPersistenceService;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

class AppStoreEventProcessor
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;
    private MessageBusInterface $bus;
    private PaymentDataFactoryInterface $paymentDataFactory;
    private ShopPersistenceService $shopPersistenceService;

    public function __construct(
        LoggerInterface $logger,
        OAuthService $oauthService,
        MessageBusInterface $bus,
        PaymentDataFactoryInterface $paymentDataFactory,
        ShopPersistenceService $shopPersistenceService
    ) {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
        $this->bus = $bus;
        $this->paymentDataFactory = $paymentDataFactory;
        $this->shopPersistenceService = $shopPersistenceService;
    }

    public function handleEvent(AppStoreLifecycleEvent $event): void
    {
        if ($event->action === AppStoreLifecycleAction::INSTALL) {
            /**
             * It a unique key which is generated for each application
             *      and is used to obtain the refresh token (required to make shop API requests).
             * You should store it for each installation.
             */
            $this->oauthService->authenticate($event);

            $paymentData = $this->paymentDataFactory->createForNewPayment(
                'External Payment '.uniqid().' from example App',
                'External payment created during installation'
            );
            $this->bus->dispatch(new CreatePaymentMessage($event->shopId, $paymentData));
        }

        if ($event->action === AppStoreLifecycleAction::UPGRADE) {
            $this->oauthService->authenticate($event);
            $this->oauthService->updateApplicationVersion($event);
            $this->logger->info('Application version update during upgrade', [
                'shop_id' => $event->shopId,
                'shop_url' => $event->shopUrl,
                'version' => $event->version,
                'success' => true
            ]);
        }

        if ($event->action === AppStoreLifecycleAction::UNINSTALL) {
            $this->logger->info('Uninstalling the application');
            $this->shopPersistenceService->uninstallShop($event->shopId, $event->shopUrl);
        }
    }
}