<?php

namespace App\Service;

use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\OAuthService;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use Psr\Log\LoggerInterface;

class AppStoreEventProcessor
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;

    public function __construct(
        LoggerInterface $logger,
        OAuthService $oauthService
    ) {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
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
            $this->createExternalPayment($event); //wynieść to do jakiegoś event listenera może?
        }

        if ($event->action === AppStoreLifecycleAction::UPGRADE) {
            $tokens = $this->oauthService->authenticate($event);

           if ($tokens) {
               $updateResult = $this->oauthService->updateApplicationVersion($event);
               $this->logger->info('Application version update during upgrade', [
                   'shop_id' => $event->shopId,
                   'shop_url' => $event->shopUrl,
                   'version' => $event->version ?? 'unknown',
                   'success' => $updateResult
               ]);
           }
        }

        if ($event->action === AppStoreLifecycleAction::UNINSTALL) {
            // Remove the application data from the database.
            $this->logger->info('Uninstalling the application');
        }
    }

    private function createExternalPayment(AppStoreLifecycleEvent $event): void
    {
        $paymentData = [
            'currencies'   => [1],
            'name'         => 'external',
            'translations' => [
                'pl_PL' => [
                    'title'   => "Płatność (" . uniqid() . ') z aplikacji zewnętrznej',
                    'lang_id' => 1,
                    'active'  => 1,
                    'description' => 'Opis płatności zewnętrznej '. rand(),
                ],
            ]
        ];

        try {
            $shopData = [
                'shop_url' => $event->shopUrl,
                'id' => $event->shopId
            ];

            $shop = $this->oauthService->getShop($shopData);
            if (!$shop) {
                throw new \RuntimeException('Failed to get shop instance');
            }

            $shopClient = $this->oauthService->getShopClient();
            if (!$shopClient) {
                throw new \RuntimeException('ShopClient is missing. Make sure the authorization has been completed.');
            }

            $paymentResource = new PaymentResource($shopClient);
            $result = $paymentResource->insert($shop, $paymentData);
            $this->logger->info('Success: External payment created using PaymentResource::insert', [
                'shop_url' => $shop->getUri(),
                'result' => $result->getData(),
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Error: Failed to create external payment using PaymentResource::insert', [
                'shop_url' => $event->shopUrl,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'request_data' => $paymentData,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
