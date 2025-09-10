<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/api/shop/payment-methods', name: 'api_shop_payment_methods_')]
class ShopPaymentMethodController extends AbstractController
{
    public function __construct(
        private readonly ShopAppInstallationRepositoryInterface $shopRepository,
        private readonly ShopPaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Verify if a payment method is supported for a shop.
     * This endpoint is public and does not require authentication.
     */
    #[Route('/verify', name: 'verify', methods: ['POST', 'GET', 'OPTIONS'])]
    #[IsGranted('PUBLIC_ACCESS')] // Jawnie oznacza endpoint jako publicznie dostępny
    public function verify(Request $request): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(['status' => 'ok']);
            $this->addCorsHeaders($response);
            return $response;
        }

        try {
            $callback = null;
            $shopUrl = null;
            $paymentMethodId = null;

            if ($request->getMethod() === 'GET') {
                $shopUrl = $request->query->get('shopUrl');
                $paymentMethodId = $request->query->get('paymentMethodId');
                $callback = $request->query->get('callback');
            } else {
                try {
                    $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
                    $shopUrl = $data['shopUrl'] ?? null;
                    $paymentMethodId = $data['paymentMethodId'] ?? null;
                } catch (JsonException $e) {
                    $shopUrl = $request->request->get('shopUrl');
                    $paymentMethodId = $request->request->get('paymentMethodId');
                }
            }

            if (is_numeric($paymentMethodId)) {
                $filteredValue = filter_var($paymentMethodId, FILTER_VALIDATE_INT);
                if ($filteredValue !== false) {
                    $paymentMethodId = $filteredValue;
                } else {
                    $this->logger->debug('Invalid payment method ID format', ['value' => $paymentMethodId]);
                }
            }

            $shopUrl = $this->normalizeShopUrl($shopUrl);
            if (!$shopUrl || !$paymentMethodId) {
                return $this->createCorsResponse([
                    'error' => 'Shop code and payment method ID are required',
                    'received' => [
                        'shopUrl' => $shopUrl,
                        'paymentMethodId' => $paymentMethodId
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            $shop = $this->shopRepository->findOneBy(['shopUrl' => $shopUrl]);
            if (!$shop) {
                $this->logger->warning(
                    'Shop not found by URL',
                    ['shopUrl' => $shopUrl],
                );

                return $this->createCorsResponse([
                    'isSupported' => false,
                    'error' => 'Shop not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $paymentMethod = $this->paymentMethodRepository->findActiveOneByShopAndPaymentMethodId(
                $shop, $paymentMethodId
            );

            $result = [
                'isSupported' => $paymentMethod !== null,
                'shopCode' => $shop->getShop(),
            ];

            if ($callback) {
                $jsonpResponse = new Response(
                    sprintf('%s(%s);', $callback, json_encode($result)),
                    Response::HTTP_OK
                );
                $jsonpResponse->headers->set('Content-Type', 'application/javascript');
                $this->addCorsHeaders($jsonpResponse);
                return $jsonpResponse;
            }

            return $this->createCorsResponse($result);

        } catch (\Throwable $e) {
            $this->logger->error('Error verifying payment method', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createCorsResponse(
                [
                    'isSupported' => false,
                    'error' => 'Internal server error: ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function normalizeShopUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return preg_replace('#^https?://#', '', $url);
    }

    private function addCorsHeaders(Response $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Origin, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600'); // Cache preflight for 1 hour
    }

    private function createCorsResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $this->addCorsHeaders($response);
        return $response;
    }
}
