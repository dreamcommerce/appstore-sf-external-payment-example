<?php

namespace App\MessageHandler;

use App\Message\CreatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreatePaymentChannelHandler
{
    private LoggerInterface $logger;
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(LoggerInterface $logger, PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->logger = $logger;
        $this->paymentChannelService = $paymentChannelService;
    }

    public function __invoke(CreatePaymentChannelMessage $message): void
    {
        $this->logger->info('Handling CreatePaymentChannelMessage', [
            'shop_code' => $message->getShopCode(),
            'payment_id' => $message->getPaymentId(),
            'locale' => $message->getLocale()
        ]);

        $this->paymentChannelService->createChannel(
            $message->getShopCode(),
            $message->getPaymentId(),
            $message->getChannelData(),
            $message->getLocale()
        );
    }
}
