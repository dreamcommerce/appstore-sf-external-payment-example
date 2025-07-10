<?php

namespace App\Service\Persistence;

use App\Domain\Shop\Model\Shop;
use App\Entity\ShopAppInstallation;
use App\Factory\ShopAppInstallationFactory;
use App\Repository\ShopAppInstallationRepository;
use App\Service\Token\TokenManagerInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class ShopPersistenceService implements ShopPersistenceServiceInterface
{
    private LoggerInterface $logger;
    private ShopAppInstallationRepository $shopAppInstallationRepository;
    private TokenManagerInterface $tokenManager;
    private ShopAppInstallationFactory $shopAppInstallationFactory;

    public function __construct(
        LoggerInterface $logger,
        ShopAppInstallationRepository $shopAppInstallationRepository,
        TokenManagerInterface $tokenManager,
        ShopAppInstallationFactory $shopAppInstallationFactory
    ) {
        $this->logger = $logger;
        $this->shopAppInstallationRepository = $shopAppInstallationRepository;
        $this->tokenManager = $tokenManager;
        $this->shopAppInstallationFactory = $shopAppInstallationFactory;
    }

    public function saveShopAppInstallation(OAuthShop $shop, string $authCode): void
    {
        if (!$shop->getToken()) {
            $this->logger->warning('Cannot save shop: Token is missing', [
                'shop_id' => $shop->getId()
            ]);
            throw new \RuntimeException('Cannot save shop: Token is missing for shop_id: ' . $shop->getId());
        }

        try {
            $shopAppInstallation = $this->shopAppInstallationFactory->fromOAuthShop($shop, $authCode);
            $tokenData = $this->tokenManager->prepareTokenResponse($shop);

            $shopToken = $this->shopAppInstallationFactory->createToken($shopAppInstallation, $tokenData);
            $shopAppInstallation->addToken($shopToken);

            $this->shopAppInstallationRepository->save($shopAppInstallation);
            $this->logger->info('Shop installation data saved successfully', [
                'shop_id' => $shop->getId(),
                'shop_url' => $shop->getUri()
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while saving shop data', [
                'shop_id' => $shop->getId(),
                'error' => $exception->getMessage()
            ]);
            throw $exception;
        }
    }

    public function updateShopToken(OAuthShop $shop): void
    {
        $shopId = (string)$shop->getId();
        $shopAppInstallation = $this->shopAppInstallationRepository->findOneBy(['shop' => $shopId]);

        if (!$shopAppInstallation) {
            $this->logger->warning('Cannot update token: Shop not found', [
                'shop_id' => $shopId
            ]);
            return;
        }

        try {
            $tokenData = $this->tokenManager->prepareTokenResponse($shop);
            $shopToken = $this->shopAppInstallationFactory->createToken($shopAppInstallation, $tokenData);
            $shopAppInstallation->addToken($shopToken);

            $this->shopAppInstallationRepository->save($shopAppInstallation);
            $this->logger->info('Shop token updated successfully', [
                'shop_id' => $shopId
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while updating shop token', [
                'shop_id' => $shopId,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function updateApplicationVersion(OAuthShop $OAuthShop, Shop $shop): void
    {
        $shopId = (string)$OAuthShop->getId();
        $shopAppInstallation = $this->shopAppInstallationRepository->findOneBy(['shop' => $shopId]);

        if (!$shopAppInstallation) {
            $this->logger->warning('Cannot update application version: Shop not found', [
                'shop_id' => $shopId
            ]);
            return;
        }

        try {
            /** @var ShopAppInstallation $shopAppInstallation  */
            $shopAppInstallation->setApplicationVersion($shop->getVersion() ?? $OAuthShop->getVersion() ?? 1);
            $this->shopAppInstallationRepository->save($shopAppInstallation);

            $this->logger->info('Shop application version updated successfully', [
                'shop_id' => $shopId,
                'version' => $shopAppInstallation->getApplicationVersion()
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while updating application version', [
                'shop_id' => $shopId,
                'error' => $exception->getMessage()
            ]);
        }
    }
}
