<?php

namespace App\Controller;

use App\Dto\Payment\EditPaymentCommand;
use App\Dto\Payment\DeletePaymentCommand;
use App\Dto\Payment\CreatePaymentCommand;
use App\Dto\ShopContextDto;
use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Factory\PaymentDataFactoryInterface;
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
        private readonly CurrencyHelper $currencyHelper,
        private readonly PaymentDataFactoryInterface $paymentDataFactory
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
        #[MapRequestPayload] DeletePaymentCommand $command
    ): Response {
        $this->logger->info('Delete payment request', [
            'shop' => $shopContext->shop,
            'payment_id' => $command->payment_id,
        ]);

        $message = new DeletePaymentMessage($shopContext->shop, $command->payment_id);
        $this->messageBus->dispatch($message);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/app-store/view/payments-configuration/edit', name: 'payments_configuration_edit', methods: ['POST'])]
    public function editPaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload] EditPaymentCommand $command
    ): Response {
        $paymentData = PaymentData::createForUpdate(
            [
                'currencies' => $command->currencies,
                'visible' => $command->visible,
                'active' => $command->active,
                'translations' => [
                    $shopContext->translations => [
                        'title' => $command->title,
                        'description' => $command->description,
                        'active' => $command->active,
                    ]
                ]
            ],
            $shopContext->translations
        );

        $message = new UpdatePaymentMessage(
            $shopContext->shop,
            $command->payment_id,
            $paymentData
        );

        $this->messageBus->dispatch($message);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/app-store/view/payments-configuration/create', name: 'create_payment', methods: ['POST'])]
    public function createPaymentAction(
        #[MapQueryString] ShopContextDto $shopContext,
        #[MapRequestPayload] CreatePaymentCommand $command
    ): Response {
        $this->logger->info('Create payment request', [
            'shop' => $shopContext->shop,
            'title' => $command->title,
            'active' => $command->active,
            'locale' => $command->locale
        ]);

        $paymentData = $this->paymentDataFactory->createFromCreateCommand($command);
        $message = new CreatePaymentMessage($shopContext->shop, $paymentData);
        $this->messageBus->dispatch($message);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
