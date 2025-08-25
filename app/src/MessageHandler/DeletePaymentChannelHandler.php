<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DeletePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeletePaymentChannelHandler
{
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(DeletePaymentChannelMessage $message): void
    {
        $this->paymentChannelService->deleteChannel(
            $message->getShopCode(),
            $message->getChannelId(),
            $message->getPaymentId()
        );
    }
}
