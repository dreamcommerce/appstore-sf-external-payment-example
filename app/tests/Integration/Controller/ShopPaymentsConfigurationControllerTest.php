<?php

namespace App\Tests\Integration\Controller;

use App\Security\HashValidator;
use App\Service\Payment\PaymentServiceInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
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

    /**
     * Mockuje wymagane serwisy dla testów integracyjnych
     */
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
                    'description' => 'Test description'
                ]
            ]);
        $container->set(PaymentServiceInterface::class, $mockPaymentService);

        $mockMessageBus = $this->createMock(MessageBusInterface::class);
        $mockMessageBus->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));
        $container->set('messenger.bus.default', $mockMessageBus);

        $mockShopContextService = $this->getMockBuilder(\App\Service\Shop\ShopContextService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockShopContextService->method('getShopAndClient')
            ->willReturn([
                'shopClient' => new \stdClass(),
                'oauthShop' => new \stdClass()
            ]);
        $container->set(\App\Service\Shop\ShopContextService::class, $mockShopContextService);

        $mockCurrencyHelper = $this->getMockBuilder(\App\Service\Payment\Util\CurrencyHelper::class)
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
        $container->set(\App\Service\Payment\Util\CurrencyHelper::class, $mockCurrencyHelper);
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
        // Generujemy parametry uwierzytelniające
        $authParams = $this->generateAuthParams(['translations' => $this->testLocale]);

        // Budujemy URL z parametrami
        $queryString = http_build_query($authParams);

        // Wykonujemy żądanie GET z parametrami uwierzytelniającymi
        $this->client->request(
            'GET',
            '/app-store/view/payments-configuration?' . $queryString
        );

        // Sprawdzamy odpowiedź
        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->dumpResponseForDebug("Payment Settings response error")
        );
        $this->assertStringContainsString('Configurable Payments from Application', $this->client->getResponse()->getContent());
    }

    public function testCreatePayment(): void
    {
        // Generujemy parametry uwierzytelniające
        $authParams = $this->generateAuthParams(['translations' => $this->testLocale]);

        // Budujemy URL z parametrami
        $queryString = http_build_query($authParams);

        // Przygotuj dane JSON do wysłania
        $paymentData = [
            'name' => 'external',
            'title' => 'Test Payment',
            'description' => 'Test payment description',
            'visible' => true,
            'locale' => $this->testLocale
        ];

        // Wykonaj żądanie POST
        $this->client->request(
            'POST',
            '/app-store/view/payments-configuration/create?' . $queryString,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($paymentData)
        );

        // Sprawdzamy odpowiedź
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $this->client->getResponse()->getStatusCode(),
            $this->dumpResponseForDebug("Create Payment response error")
        );

        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    public function testEditPayment(): void
    {
        // Generujemy parametry uwierzytelniające
        $authParams = $this->generateAuthParams(['translations' => $this->testLocale]);

        // Budujemy URL z parametrami
        $queryString = http_build_query($authParams);

        // Przygotuj dane JSON do wysłania z poprawnymi ID walut
        $paymentData = [
            'payment_id' => 1, // ID istniejącej płatności
            'name' => 'external',
            'visible' => false,
            'active' => true,
            'title' => 'Updated Payment',
            'description' => 'Updated description',
            'currencies' => [1, 2, 3] // ID walut jako liczby całkowite
        ];

        // Wykonaj żądanie POST
        $this->client->request(
            'POST',
            '/app-store/view/payments-configuration/edit?' . $queryString,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($paymentData)
        );

        // Sprawdzamy odpowiedź
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $this->client->getResponse()->getStatusCode(),
            $this->dumpResponseForDebug("Edit Payment response error")
        );

        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    public function testDeletePayment(): void
    {
        // Generujemy parametry uwierzytelniające
        $authParams = $this->generateAuthParams(['translations' => $this->testLocale]);

        // Budujemy URL z parametrami
        $queryString = http_build_query($authParams);

        // Przygotuj dane JSON do wysłania
        $paymentData = [
            'payment_id' => 1 // ID istniejącej płatności
        ];

        // Wykonaj żądanie POST
        $this->client->request(
            'POST',
            '/app-store/view/payments-configuration/delete?' . $queryString,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($paymentData)
        );

        // Sprawdzamy odpowiedź
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $this->client->getResponse()->getStatusCode(),
            $this->dumpResponseForDebug("Delete Payment response error")
        );

        $this->assertEmpty($this->client->getResponse()->getContent());
    }
}
