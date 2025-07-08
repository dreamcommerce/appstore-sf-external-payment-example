<?php

namespace App\Service\Payment;

use App\Domain\Shop\Model\Shop;
use App\Repository\ShopInstalledRepository;
use App\Service\OAuth\OAuthService;
use App\Service\Payment\Util\CurrencyHelper;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use Psr\Log\LoggerInterface;

class PaymentService implements PaymentServiceInterface
{
    private LoggerInterface $logger;
    private OAuthService $oauthService;
    private ShopInstalledRepository $shopInstalledRepository;
    private CurrencyHelper $currencyHelper;

    public function __construct(
        LoggerInterface $logger,
        OAuthService $oauthService,
        ShopInstalledRepository $shopInstalledRepository,
        CurrencyHelper $currencyHelper
    ) {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
        $this->shopInstalledRepository = $shopInstalledRepository;
        $this->currencyHelper = $currencyHelper;
    }

    public function createPayment(string $shopCode, string $name, string $title, string $description, bool $visible, array $currencies, string $locale): bool
    {
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            $this->logger->error('Shop not found when creating payment', ['shop_code' => $shopCode]);
            return false;
        }
        
        $paymentData = [
            'currencies' => $currencies,
            'name' => $name,
            'visible' => $visible ? 1 : 0,
            'translations' => [
                $locale => [
                    'title' => $title,
                    'lang_id' => 1, // Zakładamy, że pl_PL ma ID 1
                    'active' => 1,
                    'description' => $description,
                ],
            ]
        ];

        try {
            $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
            $oauthShop = $this->oauthService->getShop($shopModel);
            
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $result = $paymentResource->insert($oauthShop, $paymentData);
            
            $this->logger->info('Payment created successfully', [
                'shop_code' => $shopCode,
                'payment_data' => $paymentData,
                'payment_id' => $result->getExternalId(),
            ]);
            
            return true;
        } catch (ApiException $e) {
            $this->logger->error('Error creating payment', [
                'shop_code' => $shopCode,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'request_data' => $paymentData,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    public function updatePayment(string $shopCode, string $paymentId, array $data): bool
    {
        if (!$shopCode || !$paymentId) {
            return false;
        }
        
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            return false;
        }

        try {
            $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
            $oauthShop = $this->oauthService->getShop($shopModel);
            
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $paymentResource->update($oauthShop, (int)$paymentId, $data);

            $this->logger->info('Payment updated successfully', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'update_data' => $data,
            ]);
            
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error updating payment', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'update_data' => $data,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    public function deletePayment(string $shopCode, string $paymentId): bool
    {
        if (!$shopCode || !$paymentId) {
            return false;
        }
        
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopInstalled) {
            return false;
        }

        try {
            $shopModel = new Shop($shopCode, $shopInstalled->getShopUrl());
            $oauthShop = $this->oauthService->getShop($shopModel);
            
            $shopClient = $this->oauthService->getShopClient();
            $paymentResource = new PaymentResource($shopClient);
            $paymentResource->delete($oauthShop, (int)$paymentId);

            $this->logger->info('Payment deleted successfully', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId
            ]);
            
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting payment', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
                $visible = isset($paymentData['visible']) ? ($paymentData['visible'] ? 'visible' : 'hidden') : 'hidden';
                $currencies = isset($paymentData['currencies']) ? json_encode($paymentData['currencies']) : '[]';
                if ($this->hasTranslationForLocale($paymentData, $locale)) {
                    $translationName = $paymentData['translations'][$locale]['title'];
                    $payments[] = [
                        'payment_id' => $paymentData['payment_id'],
                        'name' => $translationName,
                        'visible' => $visible,
                        'currencies' => $currencies,
                    ];
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

    public function getPaymentById(string $shopCode, string $paymentId, string $locale): array
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
            $payment = $paymentResource->find($oauthShop, (int)$paymentId);
            $data = $payment->getData();

            if (isset($data['currencies']) && is_array($data['currencies'])) {
                $data['currencies'] = $this->currencyHelper->getCurrenciesDetails($shopClient, $oauthShop, $data['currencies']);
            } else {
                $data['currencies'] = [];
            }

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
