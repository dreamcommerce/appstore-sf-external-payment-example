<?php

namespace App\Controller;

use App\Dto\PaymentDto;
use App\Dto\ShopContextDto;
use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Shop\ShopContextService;
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
        private readonly MessageBusInterface $messageBus,
        private readonly ShopContextService $shopContextService,
        private readonly CurrencyHelper $currencyHelper
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

        $availableCurrencies = [];
        try {
            $shopData = $this->shopContextService->getShopAndClient($shopContext->shop);
            if ($shopData) {
                $currencies = $this->currencyHelper->getAllCurrencies($shopData['shopClient'], $shopData['oauthShop']);
                foreach ($currencies as $currency) {
                    $availableCurrencies[$currency['currency_id']] = [
                        'name' => $currency['name']
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch available currencies', [
                'exception' => $e->getMessage()
            ]);
        }

        return $this->render('payments-configuration.twig', [
            'paymentSettings' => $paymentSettings,
            'availableCurrencies' => $availableCurrencies
        ]);
    }

    #[Route('/app-store/view/payments-configuration/delete', name: 'payments_configuration_delete', methods: ['POST'])]
    public function deletePaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload] PaymentDto $paymentDto
    ): Response {
        $this->logger->info('Delete payment request', [
            'shop' => $shopContext->shop,
            'payment_id' => $paymentDto->payment_id,
        ]);

        $paymentId = (int) $paymentDto->payment_id;
        $message = new DeletePaymentMessage($shopContext->shop, $paymentId);
        $this->messageBus->dispatch($message);

        return $this->json(['message' => 'Payment deletion request accepted'], Response::HTTP_ACCEPTED);
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
        $paymentId = (int)$paymentDto->payment_id;
        $message = new UpdatePaymentMessage(
            $shopContext->shop,
            $paymentId,
            $paymentData
        );

        $this->messageBus->dispatch($message);

        return $this->json(['message' => 'Payment update request accepted'], Response::HTTP_ACCEPTED);
    }

    #[Route('/app-store/view/payments-configuration/create', name: 'payments_configuration_create', methods: ['POST'])]
    public function createPaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload(validationGroups: ['create'])] PaymentDto $paymentDto
    ): Response {
        $locale = $paymentDto->locale;
        $active = $paymentDto->active ?? true;
        $currencies = $paymentDto->currencies ?? [];
        $supportedCurrencies = [];

        $this->logger->info('Create payment request', [
            'shop' => $shopContext->shop,
            'title' => $paymentDto->title,
            'active' => $active,
            'locale' => $locale
        ]);

        if (!empty($currencies)) {
            try {
                $shopData = $this->shopContextService->getShopAndClient($shopContext->shop);
                if ($shopData) {
                    $supportedCurrencies = $this->currencyHelper->mapCurrencyIdsToSupportedCurrencies(
                        $shopData['shopClient'],
                        $shopData['oauthShop'],
                        $currencies
                    );
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to map currency IDs to currency codes', [
                    'exception' => $e->getMessage(),
                    'currencies' => $currencies
                ]);
            }
        }

        $paymentData = PaymentData::createForNewPayment(
            $paymentDto->title,
            $paymentDto->description ?? '',
            $active,
            $locale,
            null,
            null,
            $currencies,
            $supportedCurrencies
        );

        $message = new CreatePaymentMessage($shopContext->shop, $paymentData);
        $this->messageBus->dispatch($message);

        return $this->json(['message' => 'Payment creation request accepted'], Response::HTTP_ACCEPTED);
    }
}
