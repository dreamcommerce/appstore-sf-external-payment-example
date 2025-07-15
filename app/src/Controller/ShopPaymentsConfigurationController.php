<?php

namespace App\Controller;

use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use App\ValueObject\PaymentData;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class ShopPaymentsConfigurationController extends AbstractController
{
    private LoggerInterface $logger;
    private PaymentServiceInterface $paymentService;
    private MessageBusInterface $messageBus;

    public function __construct(
        LoggerInterface $logger,
        PaymentServiceInterface $paymentService,
        MessageBusInterface $messageBus
    ) {
        $this->logger = $logger;
        $this->paymentService = $paymentService;
        $this->messageBus = $messageBus;
    }

    #[Route('/app-store/view/payments-configuration', name: 'payments_configuration', methods: ['GET'])]
    public function paymentSettingsAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $locale = $request->query->get('translations', 'pl_PL');

        $paymentSettings = $this->paymentService->getPaymentSettingsForShop($shopCode, $locale);

        return $this->render('payments-configuration.twig', [
            'paymentSettings' => $paymentSettings
        ]);
    }

    #[Route('/app-store/view/payments-configuration/delete', name: 'payments_configuration_delete', methods: ['POST'])]
    public function deletePaymentAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->request->get('payment_id');

        $this->logger->info('Delete payment request', [
            'shop' => $shopCode,
            'payment_id' => $paymentId,
            'query_params' => $request->query->all(),
            'request_params' => $request->request->all()
        ]);

        if (!$shopCode || !$paymentId) {
            return $this->json(['success' => false, 'error' => 'Missing required data'], 400);
        }

        try {
            $message = new DeletePaymentMessage($shopCode, $paymentId);
            $this->messageBus->dispatch($message);
            return $this->json(['success' => true, 'message' => 'Delete request accepted for processing.']);
        } catch (\Exception $e) {
            $this->logger->error('Controller error during payment delete', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'shop' => $shopCode,
            ]);
            return $this->json(['success' => false, 'error' => 'Error while deleting payment'], 500);
        }
    }

    #[Route('/app-store/view/payments-configuration/edit', name: 'payments_configuration_edit', methods: ['POST'])]
    public function editPaymentAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $paymentId = $request->request->get('payment_id');
        $visible = $request->request->get('visible');
        $active = $request->request->get('active');
        $locale = $request->query->get('translations', 'pl_PL');

        if (!$shopCode || !$paymentId) {
            return $this->json(['success' => false, 'error' => 'Missing required data'], 400);
        }

        $updateData = [
            'currencies' => [1]
        ];

        if ($visible !== null) {
            $updateData['visible'] = $visible === '1';
        }

        if ($active !== null) {
            $updateData['active'] = $active === '1';
            $updateData['title'] = $request->request->get('name');
        }

        try {
            $paymentData = PaymentData::createForUpdate($updateData, $locale);
            $message = new UpdatePaymentMessage($shopCode, $paymentId, $paymentData);
            $this->messageBus->dispatch($message);
            return $this->json(['success' => true, 'message' => 'Edit request accepted for processing.']);
        } catch (\Throwable $e) {
            $this->logger->error('Controller error during payment edit', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'shop' => $shopCode,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->json(['success' => false, 'error' => 'An error occurred while editing the payment'], 500);
        }
    }

    #[Route('/app-store/view/payments-configuration/create', name: 'payments_configuration_create', methods: ['POST'])]
    public function createPaymentAction(Request $request): Response
    {
        $shopCode = $request->query->get('shop');
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $active = $request->request->get('visible') === '1';
        $locale = $request->request->get('locale', 'pl_PL');

        $this->logger->info('Create payment request', [
            'shop' => $shopCode,
            'title' => $title,
            'active' => $active,
            'locale' => $locale
        ]);

        if (!$shopCode || !$title) {
            return $this->json(['success' => false, 'error' => 'Missing required data'], 400);
        }

        try {
            // Utworzenie Value Object zgodnie z nowym interfejsem
            $paymentData = PaymentData::createForNewPayment(
                $title,
                $description,
                $active,
                [1], // Default PLN currency ID
                ['PLN'], // Supported currencies
                $locale
            );

            $message = new CreatePaymentMessage($shopCode, $paymentData);
            $this->messageBus->dispatch($message);
            return $this->json(['success' => true, 'message' => 'Create request accepted for processing.']);
        } catch (\Exception $e) {
            $this->logger->error('Controller error during payment creation', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'shop' => $shopCode,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->json(['success' => false, 'error' => 'Error while creating payment'], 500);
        }
    }
}
