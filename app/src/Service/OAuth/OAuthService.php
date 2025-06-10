<?php

namespace App\Service\OAuth;

use App\Service\OAuth\Exception\ShopNotInitializedException;
use DreamCommerce\Component\Common\Factory\DateTimeFactory;
use DreamCommerce\Component\ShopAppstore\Api\Authenticator\OAuthAuthenticator;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\Application;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use DreamCommerce\Component\ShopAppstore\Model\Token;
use Psr\Log\LoggerInterface;

class OAuthService
{
    private LoggerInterface $logger;
    private string $appClientId;
    private string $appSecret;
    private string $appStoreSecret;

    private ?OAuthAuthenticator $authenticator = null;
    private ShopClient $shopClient;
    private ?OAuthShop $shop = null;

    public function __construct(
        LoggerInterface $logger,
        ShopClient $shopClient,
        string $appClientId,
        string $appSecret,
        string $appStoreSecret
    ) {
        $this->logger = $logger;
        $this->shopClient = $shopClient;
        $this->appClientId = $appClientId;
        $this->appSecret = $appSecret;
        $this->appStoreSecret = $appStoreSecret;
    }

    public function getAccessToken(): ?string
    {
        if (!$this->shop) {
            throw new ShopNotInitializedException('Cannot get access token: OAuthShop is not initialized.');
        }
        return $this->shop->getToken()?->getAccessToken();
    }

    public function getRefreshToken(): ?string
    {
        if (!$this->shop) {
            throw new ShopNotInitializedException('Cannot get refresh token: OAuthShop is not initialized.');
        }
        return $this->shop->getToken()?->getRefreshToken();
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        if (!$this->shop) {
            throw new ShopNotInitializedException('Cannot get expiresAt: OAuthShop is not initialized.');
        }
        return $this->shop->getToken()?->getExpiresAt();
    }

    public function getAuthenticator(): ?OAuthAuthenticator
    {
        return $this->authenticator;
    }

    public function getShop(): OAuthShop
    {
        return $this->shop;
    }

    public function getShopClient(): ShopClient
    {
        return $this->shopClient;
    }

    private function createOAuthAuthenticator(): OAuthAuthenticator
    {
        $this->authenticator = new OAuthAuthenticator($this->getShopClient());
        return $this->authenticator;
    }

    /**
     * Authorize with OAuth and return token data
     *
     * @return array{access_token: string, refresh_token: string, expires_at: \DateTimeInterface|null}|null
     */
    public function authenticate(string $shopUrl, string $authCode = ''): ?array
    {
        try {
            $this->logger->debug('Starting OAuth authentication', [
                'shop_url' => $shopUrl,
                'auth_code' => $authCode,
            ]);

            if (!empty($authCode)) {
                $shop = $this->createOAuthShop($shopUrl, ['auth_code' => $authCode]);
            } else {
                $shop = $this->getOAuthShop($shopUrl);
            }

            if ($shop->isAuthenticated()) {
                $this->logger->info('Shop is already authenticated');
            } else {
                $authenticator = $this->createOAuthAuthenticator();
                $authenticator->authenticate($shop);
                $this->logger->info('OAuth authentication successful');
            }

            return [
                'access_token' => $this->getAccessToken(),
                'refresh_token' => $this->getRefreshToken(),
                'expires_at' => $this->getExpiresAt()
            ];
        } catch (\Exception $e) {
            $this->logger->error('OAuth authentication error', [
                'shop_url' => $shopUrl,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function createApplication(): Application
    {
        return new Application(
            $this->appClientId,
            $this->appSecret,
            $this->appStoreSecret
        );
    }

    private function createOAuthShop(string $shopUrl, array $shopData = []): OAuthShop
    {
        $dateTimeFactory = new DateTimeFactory();
        $shop = new OAuthShop($shopData, $dateTimeFactory);
        $shop->setApplication($this->createApplication());
        $shop->setUri($this->shopClient->getHttpClient()->createUri($shopUrl));

        $this->shop = $shop;
        return $shop;
    }

    /**
     * Returns an authorized OAuthShop instance for the given shop URL.
     */
    public function getOAuthShop(string $shopUrl): ?OAuthShop
    {
        $tokenData = [
            'refresh_token' => '',
            'access_token' => '',
        ]; // upcoming, loading shop by ID from DB

        $shop = $this->createOAuthShop($shopUrl);
        $token = new Token();
        $token->setAccessToken($tokenData['access_token']);
        $token->setRefreshToken($tokenData['refresh_token']);
        $shop->setToken($token);

        return $shop;
    }
}