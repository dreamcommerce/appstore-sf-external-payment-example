<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ShopPaymentMethod;
use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/shop/payment-methods', name: 'api_shop_payment_methods_')]
final class ShopPaymentMethodController extends AbstractController
{
    public function __construct(
        private readonly ShopAppInstallationRepositoryInterface $shopRepository,
        private readonly ShopPaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $shopCode = $request->query->get('shopCode');
        if (!$shopCode) {
            return new JsonResponse(['error' => 'Shop code is required'], Response::HTTP_BAD_REQUEST);
        }

        $shop = $this->shopRepository->findOneBy(['shop' => $shopCode]);
        if (!$shop) {
            return new JsonResponse(['error' => 'Shop not found'], Response::HTTP_NOT_FOUND);
        }

        $paymentMethods = $this->paymentMethodRepository->findBy([
            'shopCode' => $shopCode,
            'removedAt' => null
        ]);
        $result = array_map(function(ShopPaymentMethod $method) {
            return [
                'id' => $method->getId(),
                'paymentMethodId' => $method->getPaymentMethodId(),
                'paymentMethodName' => $method->getPaymentMethodName(),
                'isActive' => $method->isActive(),
            ];
        }, $paymentMethods);

        return new JsonResponse(['paymentMethods' => $result]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $paymentMethod = $this->paymentMethodRepository->find($id);
        if (!$paymentMethod || $paymentMethod->getRemovedAt() !== null) {
            return new JsonResponse(['error' => 'Payment method not found'], Response::HTTP_NOT_FOUND);
        }

        $this->paymentMethodRepository->remove($paymentMethod);
        return new JsonResponse(['message' => 'Payment method deactivated']);
    }

    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $shopCode = $data['shopCode'] ?? null;
            $paymentMethodId = $data['paymentMethodId'] ?? null;

            if (!$shopCode || !$paymentMethodId) {
                return new JsonResponse(
                    ['error' => 'Shop code and payment method ID are required'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $shop = $this->shopRepository->findOneBy(['shop' => $shopCode]);
            if (!$shop) {
                return new JsonResponse(['error' => 'Shop not found'], Response::HTTP_NOT_FOUND);
            }

            $paymentMethod = $this->paymentMethodRepository->findActiveOneByShopAndPaymentMethodId(
                $shop,
                $paymentMethodId
            );

            return new JsonResponse([
                'isSupported' => $paymentMethod !== null,
                'shopCode' => $shop->getShop(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Error verifying payment method', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(
                ['error' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
