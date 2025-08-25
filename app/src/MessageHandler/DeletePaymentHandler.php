<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DeletePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeletePaymentHandler
{
    private PaymentServiceInterface $paymentService;

    public function __construct(PaymentServiceInterface $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function __invoke(DeletePaymentMessage $message): void
    {
        $this->paymentService->deletePayment(
            $message->getShopCode(),
            $message->getPaymentId()
        );
    }
}
