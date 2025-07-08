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

    public function __invoke(CreatePaymentMessage $message): bool
    {
        $this->logger->info('Handling CreatePaymentMessage', [
            'shop_code' => $message->getShopCode(),
            'name' => $message->getName(),
            'title' => $message->getTitle()
        ]);
        
        return $this->paymentService->createPayment(
            $message->getShopCode(),
            $message->getName(),
            $message->getTitle(),
            $message->getDescription(),
            $message->isVisible(),
            $message->getCurrencies(),
            $message->getLocale()
        );
    }
}
