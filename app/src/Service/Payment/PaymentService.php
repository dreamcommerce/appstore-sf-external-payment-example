<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Dto\PaymentDto;
use App\Entity\ShopAppInstallation;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Shop\ShopContextService;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentResource;
use App\Service\Persistence\PaymentMethodPersistenceServiceInterface;

/**
 * Service responsible for handling payment operations
 */
class PaymentService implements PaymentServiceInterface
{
    private CurrencyHelper $currencyHelper;
    private ShopContextService $shopContextService;
    private PaymentMethodPersistenceServiceInterface $paymentMethodPersistenceService;

    public function __construct(
        CurrencyHelper $currencyHelper,
        ShopContextService $shopContextService,
        PaymentMethodPersistenceServiceInterface $paymentMethodPersistenceService
    ) {
        $this->currencyHelper = $currencyHelper;
        $this->shopContextService = $shopContextService;
        $this->paymentMethodPersistenceService = $paymentMethodPersistenceService;
    }

    public function createPayment(ShopAppInstallation $shop, string $name, array $translations, array $currencies, array $supportedCurrencies = []): void
    {
        $shopData = $this->getShopDataOrThrow($shop->getShop());
        $paymentData = [
            'currencies' => $currencies,
            'name' => $name,
            'translations' => $translations,
            'supportedCurrencies' => !empty($supportedCurrencies) ? $supportedCurrencies : ['PLN'],
        ];
        $paymentResource = new PaymentResource($shopData['shopClient']);
        $result = $paymentResource->insert($shopData['oauthShop'], $paymentData);

        $this->paymentMethodPersistenceService->persistPaymentMethod($shop, $result->getExternalId());
    }

    public function updatePayment(string $shopCode, int $paymentId, array $data): void
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
        $paymentResource = new PaymentResource($shopData['shopClient']);
        $paymentResource->update($shopData['oauthShop'], $paymentId, $data);
    }

    public function deletePayment(string $shopCode, int $paymentId): void
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
        $paymentResource = new PaymentResource($shopData['shopClient']);
        $paymentResource->delete($shopData['oauthShop'], $paymentId);

        $this->paymentMethodPersistenceService->removePaymentMethod($shopData['shopEntity'], $paymentId);
    }

    public function getPaymentSettingsForShop(string $shopCode, string $locale): array
    {
        if (!$shopCode) {
            return [];
        }
        $shopData = $this->getShopDataOrThrow($shopCode);
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

        return $payments;
    }

    public function getPaymentById(string $shopCode, int $paymentId, string $locale): ?array
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
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
    }

    private function hasTranslationForLocale(array $paymentData, string $locale): bool
    {
        return isset($paymentData['translations']) &&
            is_array($paymentData['translations']) &&
            isset($paymentData['translations'][$locale]['title']);
    }

    private function getShopDataOrThrow(string $shopCode): array
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if ($shopData === null) {
            throw new \RuntimeException('Shop not found for code: ' . $shopCode);
        }
        return $shopData;
    }
}
