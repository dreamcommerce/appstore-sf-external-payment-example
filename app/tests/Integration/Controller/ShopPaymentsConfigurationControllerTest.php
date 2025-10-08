<?php

namespace App\Tests\Integration\Controller;

use App\Security\HashValidator;
use App\Service\OAuth\Authentication\AuthenticationServiceInterface;
use App\Service\OAuth\OAuthService;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use App\Service\Shop\ShopContextService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Mockery;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ShopPaymentsConfigurationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $testShop = 'test-shop';
    private string $testLocale = 'en_US';

    protected function setUp(): void
    {
        $this->client = static::createClient([
            'debug' => true
        ]);

        $this->mockServices();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    private function mockServices(): void
    {
        $container = $this->client->getContainer();
        $mockHashValidator = new class('dummy_secret') extends HashValidator {
            public function __construct(string $appStoreSecret)
            {
                parent::__construct($appStoreSecret);
            }

            public function isValid(array $requestHashParams): bool
            {
                return true;
            }
        };
        $container->set(HashValidator::class, $mockHashValidator);

        $authService = Mockery::mock(AuthenticationServiceInterface::class);
        $shopPersistenceService = Mockery::mock(ShopPersistenceServiceInterface::class);
        $oAuthService = Mockery::mock(OAuthService::class);

        $container->set(AuthenticationServiceInterface::class, $authService);
        $container->set(ShopPersistenceServiceInterface::class, $shopPersistenceService);
        $container->set(OAuthService::class, $oAuthService);

        $mockPaymentService = $this->createMock(PaymentServiceInterface::class);
        $mockPaymentService->method('getPaymentSettingsForShop')
            ->willReturn([
                [
                    'payment_id' => 1,
                    'name' => 'test-payment',
                    'visible' => true,
                    'active' => true,
                    'currencies' => [1, 2, 3],
                    'title' => 'Test Payment',
                    'description' => 'Test description',
                ]
            ]);
        $container->set(PaymentServiceInterface::class, $mockPaymentService);

        $mockMessageBus = $this->createMock(MessageBusInterface::class);
        $mockMessageBus->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));
        $container->set('messenger.bus.default', $mockMessageBus);

        $mockShopContextService = $this->getMockBuilder(ShopContextService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockShopContextService->method('getShopAndClient')
            ->willReturn([
                'shopClient' => new \stdClass(),
                'oauthShop' => new \stdClass(),
                'shopEntity' => new \stdClass()
            ]);
        $container->set(ShopContextService::class, $mockShopContextService);

        $mockCurrencyHelper = $this->getMockBuilder(CurrencyHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCurrencyHelper->method('getAllCurrencies')
            ->willReturn([
                ['currency_id' => 1, 'name' => 'PLN'],
                ['currency_id' => 2, 'name' => 'USD'],
                ['currency_id' => 3, 'name' => 'EUR']
            ]);
        $mockCurrencyHelper->method('mapCurrencyIdsToSupportedCurrencies')
            ->willReturn(['PLN', 'USD', 'EUR']);
        $mockCurrencyHelper->method('getCurrenciesDetails')
            ->willReturn([
                ['currency_id' => 1, 'name' => 'PLN', 'code' => 'PLN'],
                ['currency_id' => 2, 'name' => 'US Dollar', 'code' => 'USD'],
                ['currency_id' => 3, 'name' => 'Euro', 'code' => 'EUR']
            ]);
        $container->set(CurrencyHelper::class, $mockCurrencyHelper);
    }

    private function generateAuthParams(array $additionalParams = []): array
    {
        $params = array_merge([
            'shop' => $this->testShop,
            'timestamp' => time(),
            'hash' => 'mock_hash_always_valid'
        ], $additionalParams);

        return $params;
    }

    private function dumpResponseForDebug(string $message = ""): string
    {
        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        if ($response->headers->get('Content-Type') === 'application/json') {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content = json_encode($decoded, JSON_PRETTY_PRINT);
            }
        }

        $profiler = $this->client->getProfile();
        $stackTrace = "";

        if ($profiler) {
            $collector = $profiler->getCollector('exception');
            if ($collector && $collector->hasException()) {
                $exception = $collector->getException();
                $stackTrace = sprintf(
                    "\nException: %s\nMessage: %s\nFile: %s:%s\nStack Trace: %s",
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getTraceAsString()
                );
            }
        }

        return sprintf(
            "%s\nStatus Code: %d\nResponse:\n%s%s",
            $message,
            $statusCode,
            $content,
            $stackTrace
        );
    }

    public function testPaymentSettings(): void
    {
        // Arrange & Act
        $authParams = $this->generateAuthParams(['translations' => $this->testLocale]);
        $queryString = http_build_query($authParams);
        $this->client->request(
            'GET',
            '/app-store/view/payments-configuration?' . $queryString
        );

        // Assert
        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->dumpResponseForDebug("Payment Settings response error")
        );
        $this->assertStringContainsString('Configurable Payments from Application', $this->client->getResponse()->getContent());
    }
}
