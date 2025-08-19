<?php

namespace App\Controller;

use App\Dto\ChannelDto;
use App\Dto\PaymentDetailsContextDto;
use App\Message\CreatePaymentChannelMessage;
use App\Message\DeletePaymentChannelMessage;
use App\Message\UpdatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use App\Service\Payment\PaymentServiceInterface;
use App\ValueObject\ChannelData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class PaymentDetailsController extends AbstractController
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly PaymentChannelServiceInterface $paymentChannelService,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('/app-store/view/payment-details', name: 'payment_details', methods: ['GET'])]
    public function paymentDetailsAction(
        #[MapQueryString(validationGroups: ['details'])] PaymentDetailsContextDto $context
    ): Response {
        $channels = [];

        if ($context->id) {
            $payment = $this->paymentService->getPaymentById($context->shop, $context->id, $context->translations);
            $channels = $this->paymentChannelService->getChannelsForPayment($context->shop, $context->id);
        }

        return $this->render('payment-details.twig', [
            'payment' => $payment ?? null,
            'language' => $context->translations,
            'channels' => $channels
        ]);
    }

    #[Route('/app-store/view/payment-details/create-channel', name: 'payment_details_create_channel', methods: ['POST'])]
    public function createChannelAction(
        #[MapQueryString(validationGroups: ['channel'])] PaymentDetailsContextDto $context,
        #[MapRequestPayload(validationGroups: ['create'])] ChannelDto $channelDto
    ): Response {
        try {
            $channelData = new ChannelData(
                0, // ID of the channel will be assigned by the API
                $channelDto->application_channel_id,
                $channelDto->type,
                [
                    $context->translations => ChannelData::createTranslation(
                        $channelDto->name,
                        $channelDto->description,
                        $channelDto->additional_info_label
                    )
                ]
            );

            $message = new CreatePaymentChannelMessage(
                $context->shop,
                $context->id,
                $channelData,
                $context->translations
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/get-channel/{channelId<\d+>}', name: 'payment_details_get_channel', methods: ['GET'], requirements: ['channelId' => '\d+'])]
    public function getChannelAction(
        #[MapQueryString(validationGroups: ['channel'])] PaymentDetailsContextDto $context,
        int $channelId
    ): Response {
        try {
            $channelData = $this->paymentChannelService->getChannel(
                $context->shop,
                $channelId,
                $context->id,
                $context->translations
            );

            if (!$channelData) {
                return $this->json(['success' => false, 'error' => 'Channel not found'], 404);
            }

            return $this->json(['success' => true, 'channel' => $channelData->toArray()]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/update-channel/{channelId<\d+>}', name: 'payment_details_update_channel', methods: ['PUT'], requirements: ['channelId' => '\d+'])]
    public function updateChannelAction(
        #[MapQueryString(validationGroups: ['channel'])] PaymentDetailsContextDto $context,
        #[MapRequestPayload(validationGroups: ['update'])] ChannelDto $channelDto,
        int $channelId
    ): Response {
        try {
            $channelData = new ChannelData(
                $channelId,
                $channelDto->application_channel_id,
                $channelDto->type,
                [
                    $context->translations => ChannelData::createTranslation(
                        $channelDto->name,
                        $channelDto->description,
                        $channelDto->additional_info_label
                    )
                ]
            );

            $message = new UpdatePaymentChannelMessage(
                $context->shop,
                $context->id,
                $channelData
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/delete-channel/{channelId<\d+>}', name: 'payment_details_delete_channel', methods: ['DELETE'], requirements: ['channelId' => '\d+'])]
    public function deleteChannelAction(
        #[MapQueryString(validationGroups: ['channel'])] PaymentDetailsContextDto $context,
        int $channelId
    ): Response {
        try {
            $message = new DeletePaymentChannelMessage(
                $context->shop,
                $channelId,
                $context->id
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
