<?php

namespace App\MessageHandler;

use App\Message\DeletePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeletePaymentHandler
{
    private LoggerInterface $logger;
    private PaymentServiceInterface $paymentService;

    public function __construct(LoggerInterface $logger, PaymentServiceInterface $paymentService)
    {
        $this->logger = $logger;
        $this->paymentService = $paymentService;
    }

    public function __invoke(DeletePaymentMessage $message): void
    {
        $this->logger->info('Handling DeletePaymentMessage', [
            'shop_code' => $message->getShopCode(),
            'payment_id' => $message->getPaymentId()
        ]);
        
        $this->paymentService->deletePayment(
            $message->getShopCode(),
            $message->getPaymentId()
        );
    }
}
