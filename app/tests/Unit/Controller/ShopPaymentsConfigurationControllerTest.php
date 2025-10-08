<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ShopPaymentsConfigurationController;
use App\Dto\Payment\EditPaymentCommand;
use App\Dto\Payment\DeletePaymentCommand;
use App\Dto\Payment\CreatePaymentCommand;
use App\Dto\ShopContextDto;
use App\Message\CreatePaymentMessage;
use App\Message\DeletePaymentMessage;
use App\Message\UpdatePaymentMessage;
use App\Service\Payment\Util\CurrencyHelper;
use App\ValueObject\PaymentData;
use App\Factory\PaymentDataFactoryInterface;
use App\Service\Payment\PaymentServiceInterface;
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
    private MockObject $paymentDataFactory;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(PaymentServiceInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->shopContextService = $this->createMock(ShopContextService::class);
        $this->currencyHelper = $this->createMock(CurrencyHelper::class);
        $this->paymentDataFactory = $this->createMock(PaymentDataFactoryInterface::class);

        $this->controller = new ShopPaymentsConfigurationController(
            $this->logger,
            $this->paymentService,
            $this->messageBus,
            $this->shopContextService,
            $this->currencyHelper,
            $this->paymentDataFactory
        );

        $this->controller = $this->getMockBuilder(ShopPaymentsConfigurationController::class)
            ->setConstructorArgs([
                $this->logger,
                $this->paymentService,
                $this->messageBus,
                $this->shopContextService,
                $this->currencyHelper,
                $this->paymentDataFactory
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
        $shopContext = new ShopContextDto('test-shop', 'en_US');
        $command = new DeletePaymentCommand(
            payment_id: 123
        );

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof DeletePaymentMessage
                    && $message->getShopCode() === 'test-shop'
                    && $message->getPaymentId() === 123;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act
        $response = $this->controller->deletePaymentAction($shopContext, $command);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testEditPaymentAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop', 'en_US');

        $command = new EditPaymentCommand(
            payment_id: 123,
            visible: true,
            active: true,
            title: 'Test Payment',
            description: 'Updated description',
            locale: 'en_US',
            currencies: ['USD', 'EUR']
        );

        $paymentData = $this->createMock(PaymentData::class);
        $paymentData->method('getTranslations')->willReturn([
            'en_US' => [
                'title' => 'Test Payment',
                'description' => 'Updated description',
                'active' => true
            ]
        ]);
        $paymentData->method('getCurrencies')->willReturn(['USD', 'EUR']);
        $this->paymentDataFactory
            ->expects($this->once())
            ->method('createForUpdateFromCommand')
            ->with($command, 'en_US')
            ->willReturn($paymentData);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($paymentData) {
                if (!($message instanceof UpdatePaymentMessage)) {
                    return false;
                }
                
                return $message->getShopCode() === 'test-shop'
                    && $message->getPaymentId() === 123
                    && $message->getPaymentData() === $paymentData;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act
        $response = $this->controller->editPaymentAction($shopContext, $command);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testCreatePaymentAction(): void
    {
        // Arrange
        $shopContext = new ShopContextDto('test-shop', 'en_US');
        $command = new CreatePaymentCommand(
            title: 'New Payment',
            description: 'New payment description',
            locale: 'en_US',
            active: true,
            currencies: [1, 2] // Currency IDs
        );

        $paymentData = $this->createMock(PaymentData::class);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Create payment request', [
                'shop' => 'test-shop',
                'title' => 'New Payment',
                'active' => true,
                'locale' => 'en_US'
            ]);

        $this->paymentDataFactory
            ->expects($this->once())
            ->method('createFromCreateCommand')
            ->with($command)
            ->willReturn($paymentData);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($paymentData) {
                return $message instanceof CreatePaymentMessage
                    && $message->getShopCode() === 'test-shop'
                    && $message->getPaymentData() === $paymentData;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // Act
        $response = $this->controller->createPaymentAction($shopContext, $command);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
