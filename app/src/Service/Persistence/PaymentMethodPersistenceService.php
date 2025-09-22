<?php

declare(strict_types=1);

namespace App\Service\Persistence;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use Psr\Log\LoggerInterface;

class PaymentMethodPersistenceService implements PaymentMethodPersistenceServiceInterface
{
    public function __construct(
        private readonly ShopPaymentMethodRepositoryInterface $shopPaymentMethodRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function persistPaymentMethod(ShopAppInstallation $shop, int $paymentMethodId): void
    {
        $existingMethod = $this->shopPaymentMethodRepository->findActiveOneByShopAndPaymentMethodId($shop, $paymentMethodId);
        if ($existingMethod) {
            $this->logger->info('Payment method already exists and is active', [
                'shopId' => $shop->getId(),
                'paymentMethodId' => $paymentMethodId
            ]);
            return;
        }

        $removedMethod = $this->shopPaymentMethodRepository->findOneBy([
            'shop' => $shop,
            'paymentMethodId' => $paymentMethodId,
            'removedAt' => ['not' => null]
        ]);
        if ($removedMethod instanceof ShopPaymentMethod) {
            $removedMethod->setRemovedAt(null);
            $this->shopPaymentMethodRepository->save($removedMethod);
            $this->logger->info('Payment method reactivated', [
                'shopId' => $shop->getId(),
                'paymentMethodId' => $paymentMethodId,
                'paymentMethodUuid' => $removedMethod->getId()
            ]);
            return;
        }

        $paymentMethod = new ShopPaymentMethod($shop, $paymentMethodId);
        $this->shopPaymentMethodRepository->save($paymentMethod);
        $this->logger->info('Payment method persisted', [
            'shopId' => $shop->getId(),
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodUuid' => $paymentMethod->getId()
        ]);
    }

    public function removePaymentMethod(ShopAppInstallation $shop, int $paymentMethodId): void
    {
        $method = $this->shopPaymentMethodRepository->findActiveOneByShopAndPaymentMethodId($shop, $paymentMethodId);
        if ($method instanceof ShopPaymentMethod) {
            $method->setRemovedAt(new \DateTimeImmutable());
            $this->shopPaymentMethodRepository->save($method);
            $this->logger->info('Payment method soft-deleted', [
                'shopId' => $shop->getId(),
                'paymentMethodId' => $paymentMethodId,
                'paymentMethodUuid' => $method->getId()
            ]);
        } else {
            $this->logger->warning('Payment method to delete not found or already removed', [
                'shopId' => $shop->getId(),
                'paymentMethodId' => $paymentMethodId
            ]);
        }
    }
}
