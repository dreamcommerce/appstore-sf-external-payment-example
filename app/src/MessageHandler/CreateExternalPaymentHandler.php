<?php

namespace App\MessageHandler;

use App\Domain\Shop\Model\Shop;
use App\Message\CreateExternalPaymentMessage;
use App\Service\OAuth\OAuthService;
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
        $shopModel = new Shop(
            $message->getShopCode(),
            $message->getShopUrl(),
            $message->getShopVersion(),
        );

        $paymentData = [
            'currencies'   => $message->getCurrencies(),
            'name'         => $message->getName(),
            'translations' => [
                $message->getLocale() => [
                    'title'         => $message->getTitle(),
                    'description'   => $message->getDescription(),
                    'lang_id'       => 1,
                    'active'        => 1
                ]
            ]
        ];

        try {
            $oauthShop = $this->oauthService->getShop($shopModel);
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $result = $paymentResource->insert($oauthShop, $paymentData);

            $this->logger->info('Payment created successfully', [
                'shop_code' => $message->getShopCode(),
                'payment_name' => $message->getName(),
                'payment_id' => $result->getExternalId(),
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Error creating payment', [
                'shop_code' => $message->getShopCode(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
