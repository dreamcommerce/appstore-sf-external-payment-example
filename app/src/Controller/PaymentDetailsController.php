<?php

namespace App\Controller;

use App\Service\Payment\PaymentServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentDetailsController extends AbstractController
{
    private PaymentServiceInterface $paymentService;

    public function __construct(PaymentServiceInterface $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[Route('/app-store/view/payment-details/{paymentId}', name: 'payment_details', methods: ['GET'])]
    public function paymentDetailsAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->query->get('payment', 61);
        $locale = $request->query->get('translations', 'pl_PL');
        $payment = null;

        if ($shopCode && $paymentId) {
            $payment = $this->paymentService->getPaymentById($shopCode, $paymentId, $locale);
        }
        return $this->render('payment-details.twig', [
            'payment' => $payment,
            'locale' => $locale
        ]);
    }
}
