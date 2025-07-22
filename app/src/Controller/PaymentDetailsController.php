<?php

namespace App\Controller;

use App\Message\CreatePaymentChannelMessage;
use App\Message\DeletePaymentChannelMessage;
use App\Message\UpdatePaymentChannelMessage;
use App\Service\Payment\PaymentChannelServiceInterface;
use App\Service\Payment\PaymentServiceInterface;
use App\ValueObject\ChannelData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class PaymentDetailsController extends AbstractController
{
    private PaymentServiceInterface $paymentService;
    private PaymentChannelServiceInterface $paymentChannelService;
    private MessageBusInterface $messageBus;

    public function __construct(
        PaymentServiceInterface $paymentService,
        PaymentChannelServiceInterface $paymentChannelService,
        MessageBusInterface $messageBus
    ) {
        $this->paymentService = $paymentService;
        $this->paymentChannelService = $paymentChannelService;
        $this->messageBus = $messageBus;
    }

    #[Route('/app-store/view/payment-details', name: 'payment_details', methods: ['GET'])]
    public function paymentDetailsAction(Request $request): Response
    {
        $paymentId = $request->query->get('id');
        $shopCode = $request->query->get('shop');
        $language = $request->query->get('translations', 'pl_PL');
        $channels = [];

        if ($shopCode && $paymentId) {
            $payment = $this->paymentService->getPaymentById($shopCode, $paymentId, $language);
            $channels = $this->paymentChannelService->getChannelsForPayment($shopCode, $paymentId);
        }
        return $this->render('payment-details.twig', [
            'payment' => $payment ?? null,
            'language' => $language,
            'channels' => $channels
        ]);
    }

    #[Route('/app-store/view/payment-details/create-channel', name: 'payment_details_create_channel', methods: ['POST'])]
    public function createChannelAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->query->get('id');
        $language = $request->query->get('translations', 'pl_PL');
        $data = json_decode($request->getContent(), true);
        if (!$shopCode || !$paymentId || !$data) {
            return $this->json(['success' => false, 'error' => 'Missing required data'], 400);
        }
        try {
            $channelData = new ChannelData(
                0, // ID of the channel will be assigned by the API
                $data['application_channel_id'] ?? '',
                !empty($data['type']) ? $data['type'] : null,
                [
                    $language => ChannelData::createTranslation(
                        $data['name'] ?? '',
                        $data['description'] ?? '',
                        $data['additional_info_label'] ?? ''
                    )
                ]
            );

            $message = new CreatePaymentChannelMessage(
                $shopCode,
                (int)$paymentId,
                $channelData,
                $language
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/get-channel/{channelId}', name: 'payment_details_get_channel', methods: ['GET'])]
    public function getChannelAction(Request $request, int $channelId): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->query->get('id');
        $language = $request->query->get('translations', 'pl_PL');
        if (!$shopCode || !$channelId || !$paymentId) {
            return $this->json(['success' => false, 'error' => 'Missing required data (shop, channelId or paymentId)'], 400);
        }
        try {
            $channelData = $this->paymentChannelService->getChannel(
                $shopCode,
                $channelId,
                (int)$paymentId,
                $language
            );

            if (!$channelData) {
                return $this->json(['success' => false, 'error' => 'Channel not found'], 404);
            }

            return $this->json(['success' => true, 'channel' => $channelData->toArray()]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/update-channel/{channelId}', name: 'payment_details_update_channel', methods: ['PUT'])]
    public function updateChannelAction(Request $request, int $channelId): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->query->get('id');
        $language = $request->query->get('translations', 'pl_PL');
        $data = json_decode($request->getContent(), true);
        if (!$shopCode || !$channelId || !$paymentId || !$data) {
            return $this->json(['success' => false, 'error' => 'Missing required data (shop, channelId or paymentId)'], 400);
        }
        try {
            $channelData = new ChannelData(
                $channelId,
                $data['application_channel_id'] ?? '',
                !empty($data['type']) ? $data['type'] : null,
                [
                    $language => ChannelData::createTranslation(
                        $data['name'] ?? '',
                        $data['description'] ?? '',
                        $data['additional_info_label'] ?? ''
                    )
                ]
            );

            $message = new UpdatePaymentChannelMessage(
                $shopCode,
                (int)$paymentId,
                $channelData
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/app-store/view/payment-details/delete-channel/{channelId}', name: 'payment_details_delete_channel', methods: ['DELETE'])]
    public function deleteChannelAction(Request $request, int $channelId): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->query->get('id');
        if (!$shopCode || !$channelId || !$paymentId) {
            return $this->json(['success' => false, 'error' => 'Missing required data (shop, channelId or paymentId)'], 400);
        }
        try {
            $message = new DeletePaymentChannelMessage(
                $shopCode,
                $channelId,
                (int)$paymentId
            );
            $this->messageBus->dispatch($message);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
