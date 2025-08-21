<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ShopPaymentsConfigurationController;
use App\Dto\PaymentDto;
use App\Dto\ShopContextDto;
use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Payment\Util\CurrencyHelper;
use App\Service\Shop\ShopContextService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ShopPaymentsConfigurationControllerTest extends TestCase
{
    private ShopPaymentsConfigurationController $controller;
    private MockObject $paymentService;
    private MockObject $messageBus;
    private MockObject $logger;
    private MockObject $shopContextService;
    private MockObject $currencyHelper;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(PaymentServiceInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->shopContextService = $this->createMock(ShopContextService::class);
        $this->currencyHelper = $this->createMock(CurrencyHelper::class);

        $this->controller = new ShopPaymentsConfigurationController(
            $this->logger,
            $this->paymentService,
            $this->messageBus,
            $this->shopContextService,
            $this->currencyHelper
        );

        $this->controller = $this->getMockBuilder(ShopPaymentsConfigurationController::class)
            ->setConstructorArgs([
                $this->logger,
                $this->paymentService,
                $this->messageBus,
                $this->shopContextService,
                $this->currencyHelper
            ])
            ->onlyMethods(['render', 'json'])
            ->getMock();

        $this->controller->method('render')
            ->willReturn(new Response('mock content'));

        $this->controller->method('json')
            ->willReturnCallback(function ($data, $status = 200) {
                return new JsonResponse($data, $status);
            });
    }

    public function testPaymentSettingsAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop', 'en_US');

        $expectedPayments = [
            [
                'payment_id' => 1,
                'name' => 'test-payment',
                'visible' => true,
                'active' => true,
                'currencies' => ['USD', 'EUR']
            ]
        ];

        $this->paymentService
            ->expects($this->once())
            ->method('getPaymentSettingsForShop')
            ->with('test-shop', 'en_US')
            ->willReturn($expectedPayments);

        // Act
        $response = $this->controller->paymentSettingsAction($shopContext);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeletePaymentAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop');
        $paymentDto = new PaymentDto(
            payment_id: 123
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Delete payment request', [
                'shop' => 'test-shop',
                'payment_id' => 123,
            ]);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof DeletePaymentMessage
                    && $message->getShopCode() === 'test-shop'
                    && $message->getPaymentId() === 123;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act & Assert
        $response = $this->controller->deletePaymentAction($shopContext, $paymentDto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testEditPaymentAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop', 'en_US');

        $paymentDto = new PaymentDto(
            payment_id: 123,
            name: 'updated-payment',
            visible: true,
            active: true,
            currencies: ['USD', 'EUR'],
            title: 'Updated Payment',
            description: 'Updated description'
        );

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof UpdatePaymentMessage
                    && $message->getShopCode() === 'test-shop'
                    && $message->getPaymentId() === 123; // Zmienione na int zamiast string
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act
        $response = $this->controller->editPaymentAction($shopContext, $paymentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testCreatePaymentAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop', 'en_US');

        $paymentDto = new PaymentDto(
            name: 'new-payment',
            visible: true,
            active: true,
            currencies: ['USD', 'EUR'],
            title: 'New Payment',
            description: 'New payment description',
            locale: 'en_US'
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Create payment request', [
                'shop' => 'test-shop',
                'title' => 'New Payment',
                'active' => true,
                'locale' => 'en_US'
            ]);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof CreatePaymentMessage
                    && $message->getShopCode() === 'test-shop';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act
        $response = $this->controller->createPaymentAction($shopContext, $paymentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }
}
