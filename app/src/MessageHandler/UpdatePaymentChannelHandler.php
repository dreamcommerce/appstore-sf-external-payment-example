<?php

namespace App\MessageHandler;

use App\Message\UpdatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePaymentChannelHandler
{
    private LoggerInterface $logger;
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(LoggerInterface $logger, PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->logger = $logger;
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(UpdatePaymentChannelMessage $message): void
    {
        $this->logger->info('Handling UpdatePaymentChannelMessage', [
            'shop_code' => $message->getShopCode(),
            'channel_id' => $message->getChannelData()->getChannelId(),
            'payment_id' => $message->getPaymentId()
        ]);

        $this->paymentChannelService->updateChannel(
            $message->getShopCode(),
            $message->getPaymentId(),
            $message->getChannelData()
        );
    }
}
