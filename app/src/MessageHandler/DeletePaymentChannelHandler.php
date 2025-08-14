<?php

namespace App\MessageHandler;

use App\Message\DeletePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeletePaymentChannelHandler
{
    private LoggerInterface $logger;
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(LoggerInterface $logger, PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->logger = $logger;
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(DeletePaymentChannelMessage $message): void
    {
        $this->logger->info('Handling DeletePaymentChannelMessage', [
            'shop_code' => $message->getShopCode(),
            'channel_id' => $message->getChannelId(),
            'payment_id' => $message->getPaymentId()
        ]);

        $this->paymentChannelService->deleteChannel(
            $message->getShopCode(),
            $message->getChannelId(),
            $message->getPaymentId()
        );
    }
}
