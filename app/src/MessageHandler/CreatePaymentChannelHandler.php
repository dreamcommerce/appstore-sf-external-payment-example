<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CreatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreatePaymentChannelHandler
{
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(CreatePaymentChannelMessage $message): void
    {
        $this->paymentChannelService->createChannel(
            $message->getShopCode(),
            $message->getPaymentId(),
            $message->getChannelData(),
            $message->getLocale()
        );
    }
}
