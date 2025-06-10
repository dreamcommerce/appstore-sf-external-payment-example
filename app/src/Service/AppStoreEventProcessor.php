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
            $authCode = $event->authCode;
            $this->oauthService->authenticate($event->shopUrl, $authCode);
            $this->createExternalPayment($event->shopUrl); //wynieść to do jakiegoś event listenera może?
        }

        if ($event->action === AppStoreLifecycleAction::UPGRADE) {
            $tokens = $this->oauthService->authenticate($event->shopUrl);
            $this->logger->debug('Tokens after update and auth', $tokens ?? []);
        }

        if ($event->action === AppStoreLifecycleAction::UNINSTALL) {
            // Remove the application data from the database.
            $this->logger->info('Uninstalling the application');
        }
    }

    private function createExternalPayment(string $shopUrl): void
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
            $shop = $this->oauthService->getOAuthShop($shopUrl);
            $shopClient = $this->oauthService->getShopClient();
            if (!$shopClient) {
                throw new \RuntimeException('ShopClient is missing. Make sure the authorization has been completed.');
            }

            $paymentResource = new PaymentResource($shopClient);
            $result = $paymentResource->insert($shop, $paymentData);
            $this->logger->info('Success: External payment created using PaymentResource::insert', [
                'shop_url' => $shopUrl,
                'result' => $result,
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Error: Failed to create external payment using PaymentResource::insert', [
                'shop_url' => $shopUrl,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'request_data' => $paymentData,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
