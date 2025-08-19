<?php

namespace App\Controller;

use App\Dto\PaymentDto;
use App\Dto\ShopContextDto;
use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use App\ValueObject\PaymentData;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class ShopPaymentsConfigurationController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PaymentServiceInterface $paymentService,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('/app-store/view/payments-configuration', name: 'payments_configuration', methods: ['GET'])]
    public function paymentSettingsAction(
        #[MapQueryString] ShopContextDto $shopContext
    ): Response {
        $paymentSettings = $this->paymentService->getPaymentSettingsForShop(
            $shopContext->shop,
            $shopContext->translations
        );

        return $this->render('payments-configuration.twig', [
            'paymentSettings' => $paymentSettings
        ]);
    }

    #[Route('/app-store/view/payments-configuration/delete', name: 'payments_configuration_delete', methods: ['POST'])]
    public function deletePaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload('payment_id')] int $paymentId
    ): Response {
        $this->logger->info('Delete payment request', [
            'shop' => $shopContext->shop,
            'payment_id' => $paymentId,
        ]);

        $message = new DeletePaymentMessage($shopContext->shop, (string)$paymentId);
        $this->messageBus->dispatch($message);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/app-store/view/payments-configuration/edit', name: 'payments_configuration_edit', methods: ['POST'])]
    public function editPaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload(validationGroups: ['edit'])] PaymentDto $paymentDto
    ): Response {
        $updateData = [
            'currencies' => $paymentDto->currencies ?? [],
            'visible' => $paymentDto->visible,
            'active' => $paymentDto->active,
            'translations' => [
                $shopContext->translations => [
                    'title' => $paymentDto->title,
                    'description' => $paymentDto->description,
                    'active' => $paymentDto->active,
                ]
            ]
        ];

        $paymentData = PaymentData::createForUpdate($updateData, $shopContext->translations);
        $message = new UpdatePaymentMessage(
            $shopContext->shop,
            (string)$paymentDto->payment_id,
            $paymentData
        );
        $this->messageBus->dispatch($message);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/app-store/view/payments-configuration/create', name: 'payments_configuration_create', methods: ['POST'])]
    public function createPaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload(validationGroups: ['create'])] PaymentDto $paymentDto
    ): Response {
        $locale = $paymentDto->locale;

        $this->logger->info('Create payment request', [
            'shop' => $shopContext->shop,
            'title' => $paymentDto->title,
            'active' => $paymentDto->active,
            'locale' => $locale
        ]);

        $paymentData = PaymentData::createForNewPayment(
            $paymentDto->title,
            $paymentDto->description ?? '',
            $paymentDto->active,
            $locale,
            null,
            null,
            $paymentDto->currencies ?? []
        );

        $message = new CreatePaymentMessage($shopContext->shop, $paymentData);
        $this->messageBus->dispatch($message);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }
}
