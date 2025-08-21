<?php

namespace App\MessageHandler;

use App\Message\CreatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreatePaymentHandler
{
    private LoggerInterface $logger;
    private PaymentServiceInterface $paymentService;

    public function __construct(LoggerInterface $logger, PaymentServiceInterface $paymentService)
    {
        $this->logger = $logger;
        $this->paymentService = $paymentService;
    }

    public function __invoke(CreatePaymentMessage $message): void
    {
        $paymentData = $message->getPaymentData();
        $this->logger->info('Handling CreatePaymentMessage', [
            'shop_code' => $message->getShopCode(),
            'name' => $paymentData->getName(),
            'title' => $paymentData->getTitle(),
            'currencies' => $paymentData->getCurrencies(),
            'supportedCurrencies' => $paymentData->getSupportedCurrencies(),
            'description' => $paymentData->getDescription($paymentData->getLocale())
        ]);
        
        $this->paymentService->createPayment(
            $message->getShopCode(),
            $paymentData->getName(),
            $paymentData->getTitle($paymentData->getLocale()),
            $paymentData->getDescription($paymentData->getLocale()),
            $paymentData->isActive($paymentData->getLocale()) ?? true,
            $paymentData->getCurrencies(),
            $paymentData->getLocale(),
            $paymentData->getSupportedCurrencies()
        );
    }
}
