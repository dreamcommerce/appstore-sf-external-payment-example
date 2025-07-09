<?php

namespace App\Service\Payment;

use App\Domain\Shop\Model\Shop;
use App\Repository\ShopInstalledRepository;
use App\Service\OAuth\OAuthService;
use App\Service\Payment\Util\CurrencyHelper;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for handling payment operations
 */
class PaymentService implements PaymentServiceInterface
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;
    private ShopInstalledRepository $shopInstalledRepository;
    private CurrencyHelper $currencyHelper;
    private PaymentMapper $paymentMapper;

    public function __construct(
        LoggerInterface $logger,
        OAuthService $oauthService,
        ShopInstalledRepository $shopInstalledRepository,
        CurrencyHelper $currencyHelper,
        PaymentMapper $paymentMapper
    ) {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
        $this->shopInstalledRepository = $shopInstalledRepository;
        $this->currencyHelper = $currencyHelper;
        $this->paymentMapper = $paymentMapper;
    }

    private function getShopAndClient(string $shopCode): ?array
    {
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            $this->logger->error('Shop not found', ['shop_code' => $shopCode]);
            return null;
        }

        $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
        $oauthShop = $this->oauthService->getShop($shopModel);
        $shopClient = $this->oauthService->getShopClient();

        return ['oauthShop' => $oauthShop, 'shopClient' => $shopClient];
    }

    private function handleApiException(\Throwable $e, string $action, array $context = []): void
    {
        $this->logger->error("Error during {$action}", array_merge($context, [
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
    }

    public function createPayment(string $shopCode, string $name, string $title, string $description, bool $visible, array $currencies, string $locale): bool
    {
        $shopData = $this->getShopAndClient($shopCode);
        if (!$shopData) {
            return false;
        }

        $paymentData = [
            'currencies' => $currencies,
            'name' => $name,
            'visible' => $visible ? 1 : 0,
            'translations' => [
                $locale => [
                    'title' => $title,
                    'lang_id' => 1, // Zakładamy, że pl_PL ma ID 1 -> TODO dorobić pobieranie default LangID
                    'active' => 1,
                    'description' => $description,
                ],
            ]
        ];

        try {
            $paymentResource = new PaymentResource($shopData['shopClient']);
            $result = $paymentResource->insert($shopData['oauthShop'], $paymentData);

            $this->logger->info('Payment created successfully', [
                'shop_code' => $shopCode,
                'payment_data' => $paymentData,
                'payment_id' => $result->getExternalId(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleApiException($e, 'creating payment', ['shop_code' => $shopCode]);
            return false;
        }
    }

    public function updatePayment(string $shopCode, int $paymentId, array $data): bool
    {
        $shopData = $this->getShopAndClient($shopCode);
        if (!$shopData) {
            return false;
        }

        try {
            $paymentResource = new PaymentResource($shopData['shopClient']);
            $paymentResource->update($shopData['oauthShop'], $paymentId, $data);

            $this->logger->info('Payment updated successfully', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'update_data' => $data,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleApiException($e, 'updating payment', ['shop_code' => $shopCode, 'payment_id' => $paymentId]);
            return false;
        }
    }

    public function deletePayment(string $shopCode, int $paymentId): bool
    {
        $shopData = $this->getShopAndClient($shopCode);
        if (!$shopData) {
            return false;
        }

        try {
            $paymentResource = new PaymentResource($shopData['shopClient']);
            $paymentResource->delete($shopData['oauthShop'], $paymentId);

            $this->logger->info('Payment deleted successfully', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleApiException($e, 'deleting payment', ['shop_code' => $shopCode, 'payment_id' => $paymentId]);
            return false;
        }
    }

    private function hasTranslationForLocale(array $paymentData, string $locale): bool
    {
        return isset($paymentData['translations']) &&
            is_array($paymentData['translations']) &&
            isset($paymentData['translations'][$locale]['title']);
    }

    public function getPaymentSettingsForShop(string $shopCode, string $locale): array
    {
        if (!$shopCode) {
            return [];
        }
        
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            return [];
        }

        try {
            $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
            $oauthShop = $this->oauthService->getShop($shopModel);
            
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $itemList = $paymentResource->findAll($oauthShop);
            $payments = [];

            foreach ($itemList as $payment) {
                $paymentData = $payment->getData();
                if ($this->hasTranslationForLocale($paymentData, $locale)) {
                    $payments[] = $this->paymentMapper->mapFromApi($paymentData, $locale);
                }
            }

            $this->logger->info('Payment settings fetched', [
                'shop_code' => $shopCode,
                'payments_count' => count($payments)
            ]);

            return $payments;
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching payment settings', [
                'shop_code' => $shopCode,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    public function getPaymentById(string $shopCode, int $paymentId, string $locale): array
    {
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            $this->logger->error('Shop not found when fetching payment', ['shop_code' => $shopCode]);
            return [];
        }

        try {
            $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
            $oauthShop = $this->oauthService->getShop($shopModel);
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $payment = $paymentResource->find($oauthShop, $paymentId);
            $data = $payment->getData();
            if (isset($data['currencies']) && is_array($data['currencies'])) {
                $data['currencies'] = $this->currencyHelper->getCurrenciesDetails($shopClient, $oauthShop, $data['currencies']);
            } else {
                $data['currencies'] = [];
            }
            $data['supportedCurrencies'] = isset($data['supportedCurrencies']) ? $data['supportedCurrencies'] : [];

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching payment by id', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
