<?php

namespace App\MessageHandler;

use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePaymentHandler
{
    private LoggerInterface $logger;
    private PaymentServiceInterface $paymentService;

    public function __construct(LoggerInterface $logger, PaymentServiceInterface $paymentService)
    {
        $this->logger = $logger;
        $this->paymentService = $paymentService;
    }

    public function __invoke(UpdatePaymentMessage $message): bool
    {
        $paymentData = $message->getPaymentData();

        $this->logger->info('Handling UpdatePaymentMessage', [
            'shop_code' => $message->getShopCode(),
            'payment_id' => $message->getPaymentId(),
            'data' => $paymentData->toArray()
        ]);
        
        return $this->paymentService->updatePayment(
            $message->getShopCode(),
            $message->getPaymentId(),
            $paymentData->toArray()
        );
    }
}
