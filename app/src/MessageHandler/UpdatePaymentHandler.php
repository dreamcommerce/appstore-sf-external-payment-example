<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePaymentHandler
{
    private PaymentServiceInterface $paymentService;

    public function __construct(PaymentServiceInterface $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function __invoke(UpdatePaymentMessage $message): void
    {
        $this->paymentService->updatePayment(
            $message->getShopCode(),
            $message->getPaymentId(),
            $message->getPaymentData()->toArray()
        );
    }
}
