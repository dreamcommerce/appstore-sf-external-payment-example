<?php

namespace App\Service;

use App\Message\CreateExternalPaymentMessage;
use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\OAuthService;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

class AppStoreEventProcessor
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;
    private MessageBusInterface $bus;

    public function __construct(
        LoggerInterface $logger,
        OAuthService $oauthService,
        MessageBusInterface $bus
    ) {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
        $this->bus = $bus;
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
            $this->bus->dispatch(new CreateExternalPaymentMessage(
                $event->shopId,
                $event->shopUrl,
                $event->version,
                'external',
                'External Payment '.uniqid().' from example App', // Example payment name must be unique
                'External payment created during installation',
                true,
                [1], // Assuming 1 is base currency ID for polish zloty
                'pl_PL' // Assuming Polish locale for the example
            ));
        }

        if ($event->action === AppStoreLifecycleAction::UPGRADE) {
            try {
                $this->oauthService->authenticate($event);
                $this->oauthService->updateApplicationVersion($event);
                $this->logger->info('Application version update during upgrade', [
                    'shop_id' => $event->shopId,
                    'shop_url' => $event->shopUrl,
                    'version' => $event->version,
                    'success' => true
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Upgrade failed', [
                    'shop_id' => $event->shopId,
                    'shop_url' => $event->shopUrl,
                    'version' => $event->version,
                    'error_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        if ($event->action === AppStoreLifecycleAction::UNINSTALL) {
            // Remove the application data from the database.
            $this->logger->info('Uninstalling the application');
        }
    }
}