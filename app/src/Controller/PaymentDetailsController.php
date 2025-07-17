<?php

namespace App\Controller;

use App\Service\Payment\PaymentChannelServiceInterface;
use App\Service\Payment\PaymentServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentDetailsController extends AbstractController
{
    private PaymentServiceInterface $paymentService;
    private PaymentChannelServiceInterface $paymentChannelService;

    public function __construct(PaymentServiceInterface $paymentService, PaymentChannelServiceInterface $paymentChannelService)
    {
        $this->paymentService = $paymentService;
        $this->paymentChannelService = $paymentChannelService;
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
        $data = json_decode($request->getContent(), true);
        if (!$shopCode || !$paymentId || !$data) {
            return $this->json(['success' => false, 'error' => 'Missing required data'], 400);
        }
        try {
            $result = $this->paymentChannelService->createChannelForPayment(
                $shopCode,
                (int)$paymentId,
                $data['type'] ?? '',
                $data['key'] ?? '',
                $data['name'] ?? '',
                $data['description'] ?? ''
            );
            return $this->json(['success' => true, 'channel' => $result]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
