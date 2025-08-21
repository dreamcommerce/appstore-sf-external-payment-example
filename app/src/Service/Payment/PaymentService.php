<?php

namespace App\Service\Payment;

use App\Dto\PaymentDto;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Shop\ShopContextService;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for handling payment operations
 */
class PaymentService implements PaymentServiceInterface
{
    private LoggerInterface $logger;
    private CurrencyHelper $currencyHelper;
    private ShopContextService $shopContextService;

    public function __construct(
        LoggerInterface $logger,
        CurrencyHelper $currencyHelper,
        ShopContextService $shopContextService
    ) {
        $this->logger = $logger;
        $this->currencyHelper = $currencyHelper;
        $this->shopContextService = $shopContextService;
    }

    public function createPayment(string $shopCode, string $name, string $title, string $description, bool $visible, array $currencies, string $locale, array $supportedCurrencies = []): void
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
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
            ],
            'supportedCurrencies' => !empty($supportedCurrencies) ? $supportedCurrencies : ['PLN'],
        ];

        $paymentResource = new PaymentResource($shopData['shopClient']);
        $result = $paymentResource->insert($shopData['oauthShop'], $paymentData);

        $this->logger->info('Payment created successfully', [
            'shop_code' => $shopCode,
            'payment_data' => $paymentData,
            'payment_id' => $result->getExternalId(),
        ]);
    }

    public function updatePayment(string $shopCode, int $paymentId, array $data): void
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        $paymentResource = new PaymentResource($shopData['shopClient']);
        $paymentResource->update($shopData['oauthShop'], $paymentId, $data);

        $this->logger->info('Payment updated successfully', [
            'shop_code' => $shopCode,
            'payment_id' => $paymentId,
            'update_data' => $data,
        ]);
    }

    public function deletePayment(string $shopCode, int $paymentId): void
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        $paymentResource = new PaymentResource($shopData['shopClient']);
        $paymentResource->delete($shopData['oauthShop'], $paymentId);

        $this->logger->info('Payment deleted successfully', [
            'shop_code' => $shopCode,
            'payment_id' => $paymentId
        ]);
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

        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            return [];
        }

        try {
            $paymentResource = new PaymentResource($shopData['shopClient']);
            $itemList = $paymentResource->findAll($shopData['oauthShop']);
            $payments = [];

            foreach ($itemList as $payment) {
                $paymentData = $payment->getData();
                if ($this->hasTranslationForLocale($paymentData, $locale)) {
                    $translation = $paymentData['translations'][$locale];
                    $payments[] = new PaymentDto(
                        (int)($paymentData['payment_id'] ?? 0),
                        (string)($paymentData['name'] ?? ''),
                        (bool)($paymentData['visible'] ?? false),
                        (bool)($translation['active'] ?? false),
                        (array)($paymentData['currencies'] ?? []),
                        (array)($paymentData['supportedCurrencies'] ?? []),
                        (string)($translation['title'] ?? ''),
                        (string)($translation['description'] ?? ''),
                        $locale
                    );
                }
            }

            $this->logger->info('Payment settings fetched', [
                'shop_code' => $shopCode,
                'payments_count' => count($payments)
            ]);

            return $payments;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getPaymentById(string $shopCode, int $paymentId, string $locale): array
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            $this->logger->error('Shop not found when fetching payment', ['shop_code' => $shopCode]);
            return [];
        }

        try {
            $paymentResource = new PaymentResource($shopData['shopClient']);
            $payment = $paymentResource->find($shopData['oauthShop'], $paymentId);
            $data = $payment->getData();
            if (isset($data['currencies']) && is_array($data['currencies'])) {
                $data['currencies'] = $this->currencyHelper->getCurrenciesDetails($shopData['shopClient'], $shopData['oauthShop'], $data['currencies']);
            } else {
                $data['currencies'] = [];
            }
            $data['supportedCurrencies'] = isset($data['supportedCurrencies']) ? $data['supportedCurrencies'] : [];

            return $data;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
