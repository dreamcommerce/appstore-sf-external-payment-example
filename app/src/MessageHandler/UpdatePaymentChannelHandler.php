<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePaymentChannelHandler
{
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(UpdatePaymentChannelMessage $message): void
    {
        $this->paymentChannelService->updateChannel(
            $message->getShopCode(),
            $message->getPaymentId(),
            $message->getChannelData()
        );
    }
}
