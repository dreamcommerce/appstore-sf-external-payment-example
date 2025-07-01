<?php

namespace App\MessageHandler;

use App\Message\CreateExternalPaymentMessage;
use App\Service\OAuth\OAuthService;
use App\Service\Shop\Shop;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateExternalPaymentHandler
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;

    public function __construct(LoggerInterface $logger, OAuthService $oauthService)
    {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
    }

    public function __invoke(CreateExternalPaymentMessage $message): void
    {
        $event = $message->getEvent();
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
            $shop = $this->oauthService->getShop(Shop::fromEvent($event));

            $shopClient = $this->oauthService->getShopClient();
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
