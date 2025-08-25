<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CreatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Shop\ShopContextService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreatePaymentHandler
{
    private ShopContextService $shopContextService;
    private PaymentServiceInterface $paymentService;

    public function __construct(
        PaymentServiceInterface $paymentService,
        ShopContextService $shopContextService
    ) {
        $this->paymentService = $paymentService;
        $this->shopContextService = $shopContextService;
    }

    public function __invoke(CreatePaymentMessage $message): void
    {
        $paymentData = $message->getPaymentData();
        $shopData = $this->shopContextService->getShopAndClient($message->getShopCode());

        if (!$shopData) {
            throw new \InvalidArgumentException('Shop not found for code: ' . $message->getShopCode());
        }

        $this->paymentService->createPayment(
            $shopData['shopEntity'],
            $paymentData->getName(),
            $paymentData->getTranslations(),
            $paymentData->getCurrencies(),
            $paymentData->getSupportedCurrencies()
        );
    }
}
