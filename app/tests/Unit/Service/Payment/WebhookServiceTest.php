<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Payment;

use App\Dto\Webhook\OrderTransactionWebhookDto;
use App\Dto\Webhook\OrderRefundWebhookDto;
use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use App\Entity\Transaction;
use App\Factory\TransactionFactory;
use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use App\Service\Payment\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class WebhookServiceTest extends TestCase
{
    private WebhookService $service;
    /** @var ShopPaymentMethodRepositoryInterface|MockObject */
    private $shopPaymentMethodRepository;
    /** @var ShopAppInstallationRepositoryInterface|MockObject */
    private $shopAppInstallationRepository;
    /** @var TransactionRepositoryInterface|MockObject */
    private $transactionRepository;
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;
    /** @var LoggerInterface|MockObject */
    private $logger;
    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcher;
    /** @var TransactionFactory|MockObject */
    private $transactionFactory;

    protected function setUp(): void
    {
        $this->shopAppInstallationRepository = $this->createMock(ShopAppInstallationRepositoryInterface::class);
        $this->shopPaymentMethodRepository = $this->createMock(ShopPaymentMethodRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->transactionFactory = $this->createMock(TransactionFactory::class);

        $this->service = new WebhookService(
            $this->shopPaymentMethodRepository,
            $this->transactionRepository,
            $this->shopAppInstallationRepository,
            $this->entityManager,
            $this->logger,
            $this->eventDispatcher,
            $this->transactionFactory
        );
    }

    public function testProcessOrderTransactionWebhookSuccess(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookDto = new OrderTransactionWebhookDto(
            orderId: 'order-123',
            paymentId: '456',
            transactionId: 'txn-789',
            currencyId: 'PLN',
            currencyValue: '99.99',
            paymentData: json_encode(['key' => 'value']),
            paymentSuccessShopLink: 'https://shop.com/success',
            paymentFailShopLink: 'https://shop.com/fail'
        );

        $shop = $this->createMock(ShopAppInstallation::class);
        $shop->method('getId')->willReturn(1);

        $paymentMethod = $this->createMock(ShopPaymentMethod::class);
        $paymentMethod->method('getId')->willReturn(10);

        $transaction = $this->createMock(Transaction::class);

        $this->transactionRepository->expects($this->once())
            ->method('findOneByExternalTransactionId')
            ->with('txn-789')
            ->willReturn(null);

        $this->shopAppInstallationRepository->expects($this->once())
            ->method('findOneByShopLicense')
            ->with($shopLicense)
            ->willReturn($shop);

        $this->shopPaymentMethodRepository->expects($this->once())
            ->method('findActiveOneByShopAndPaymentMethodId')
            ->with($shop, 456)
            ->willReturn($paymentMethod);

        $this->transactionFactory->expects($this->once())
            ->method('createOrderTransaction')
            ->with(
                $this->equalTo($paymentMethod),
                $this->equalTo('txn-789'),
                $this->equalTo('PLN'),
                $this->equalTo('99.99'),
                $this->equalTo('order-123'),
                $this->equalTo('456'),
                $this->equalTo(json_encode(['key' => 'value'])),
                $this->equalTo('https://shop.com/success'),
                $this->equalTo('https://shop.com/fail')
            )
            ->willReturn($transaction);

        $this->transactionRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($transaction));

        // Act
        $this->service->processOrderTransactionWebhook($webhookDto, $shopLicense);
    }

    public function testProcessOrderTransactionWebhookSkipsWhenTransactionExists(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookDto = new OrderTransactionWebhookDto(
            orderId: 'order-123',
            paymentId: '456',
            transactionId: 'txn-789',
            currencyId: 'PLN',
            currencyValue: '99.99',
            paymentData: null,
            paymentSuccessShopLink: null,
            paymentFailShopLink: null
        );

        $existingTransaction = $this->createMock(Transaction::class);

        // Mock transaction repository to return existing transaction
        $this->transactionRepository->expects($this->once())
            ->method('findOneByExternalTransactionId')
            ->with('txn-789')
            ->willReturn($existingTransaction);

        // Should not save transaction
        $this->transactionRepository->expects($this->never())
            ->method('save');


        // Act
        $this->service->processOrderTransactionWebhook($webhookDto, $shopLicense);
    }

    public function testProcessOrderTransactionWebhookReturnsWhenShopNotFound(): void
    {
        // Arrange
        $shopLicense = 'invalid-shop-license';
        $webhookDto = new OrderTransactionWebhookDto(
            orderId: 'order-123',
            paymentId: '456',
            transactionId: 'txn-789',
            currencyId: 'PLN',
            currencyValue: '99.99',
            paymentData: null,
            paymentSuccessShopLink: null,
            paymentFailShopLink: null
        );

        $this->transactionRepository->expects($this->once())
            ->method('findOneByExternalTransactionId')
            ->with('txn-789')
            ->willReturn(null);

        $this->shopAppInstallationRepository->expects($this->once())
            ->method('findOneByShopLicense')
            ->with($shopLicense)
            ->willReturn(null);

        $this->shopPaymentMethodRepository->expects($this->never())
            ->method('findActiveOneByShopAndPaymentMethodId');
            
        $this->transactionRepository->expects($this->never())
            ->method('save');

        // Assert logging
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing order transaction webhook', [
                'shop_license' => $shopLicense,
                'transaction_id' => 'txn-789',
                'order_id' => 'order-123',
                'payment_id' => '456'
            ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Shop not found for webhook', [
                'shop_license' => $shopLicense
            ]);

        // Act
        $this->service->processOrderTransactionWebhook($webhookDto, $shopLicense);
    }

    public function testProcessOrderTransactionWebhookReturnsWhenPaymentMethodNotFound(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookDto = new OrderTransactionWebhookDto(
            orderId: 'order-123',
            paymentId: '456',
            transactionId: 'txn-789',
            currencyId: 'PLN',
            currencyValue: '99.99',
            paymentData: null,
            paymentSuccessShopLink: null,
            paymentFailShopLink: null
        );

        $shop = $this->createMock(ShopAppInstallation::class);
        $shop->method('getId')->willReturn(1);

        // Mock transaction repository to return no existing transaction
        $this->transactionRepository->expects($this->once())
            ->method('findOneByExternalTransactionId')
            ->with('txn-789')
            ->willReturn(null);

        // Mock shop repository to return the shop
        $this->shopAppInstallationRepository->expects($this->once())
            ->method('findOneByShopLicense')
            ->with($shopLicense)
            ->willReturn($shop);

        // Mock payment method finding (payment method not found)
        $this->shopPaymentMethodRepository->expects($this->once())
            ->method('findActiveOneByShopAndPaymentMethodId')
            ->with($shop, 456)
            ->willReturn(null);

        // Should not save transaction
        $this->transactionRepository->expects($this->never())->method('save');

        // Mock logging
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing order transaction webhook', $this->callback(function($context) {
                $this->assertArrayHasKey('shop_license', $context);
                return true;
            }));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) {
                return $event instanceof \App\Event\Transaction\TransactionFailedEvent
                    && $event->getTransactionId() === 'txn-789'
                    && $event->getReason() === 'Payment method not found';
            }));

        // Act
        $this->service->processOrderTransactionWebhook($webhookDto, $shopLicense);
    }

    public function testProcessOrderRefundWebhookLogsWarningForIncompleteImplementation(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookDto = new OrderRefundWebhookDto(
            refundId: 'refund-123',
            transactionId: 'txn-789',
            status: 'completed',
            currencyId: 'PLN',
            currencyValue: '50.00',
            comment: 'Customer requested refund',
            date: new \DateTimeImmutable('2025-09-11T15:30:00+00:00')
        );

        $shop = $this->createMock(ShopAppInstallation::class);
        $shop->method('getId')->willReturn(1);

        // Create a mock EntityRepository that will be returned by getRepository
        $repositoryMock = $this->getMockBuilder(\Doctrine\ORM\EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        // Configure the mock to return null for any findOneBy call
        $repositoryMock->method('findOneBy')
            ->willReturn(null);

        // Mock the entity manager to return our mock repository
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Transaction::class)
            ->willReturn($repositoryMock);

        // The service should call findOneBy on the repository, not findOneByExternalTransactionId
        $this->transactionRepository->expects($this->never())
            ->method('findOneByExternalTransactionId');

        // Mock shop lookup
        $this->shopAppInstallationRepository->expects($this->once())
            ->method('findOneByShopLicense')
            ->with($shopLicense)
            ->willReturn($shop);

        // Should log the webhook processing
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Processing order refund webhook', [
                'refund_id' => 'refund-123',
                'transaction_id' => 'txn-789',
                'shop_license' => $shopLicense
            ]);

        // Should log a warning about incomplete implementation
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Refund webhook processing not fully implemented', [
                'refund_id' => 'refund-123',
                'shop_license' => $shopLicense
            ]);

        // Should not save transaction
        $this->transactionRepository->expects($this->never())
            ->method('save');

        // Act
        $this->service->processOrderRefundWebhook($webhookDto, $shopLicense);
    }

    public function testProcessOrderRefundWebhookSkipsWhenTransactionExists(): void
    {
        // Arrange
        $shopLicense = 'test-shop-license-123';
        $webhookDto = new OrderRefundWebhookDto(
            refundId: 'refund-123',
            transactionId: 'txn-789',
            status: 'completed',
            currencyId: 'PLN',
            currencyValue: '50.00',
            comment: 'Customer requested refund',
            date: new \DateTimeImmutable('2025-09-11T15:30:00+00:00')
        );

        $existingTransaction = $this->createMock(Transaction::class);

        // Create a mock EntityRepository that will be returned by getRepository
        $repositoryMock = $this->getMockBuilder(\Doctrine\ORM\EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        // Configure the mock to return the existing transaction for the specific findOneBy call
        $repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['externalTransactionId' => 'txn-789'])
            ->willReturn($existingTransaction);

        // Mock the entity manager to return our mock repository
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Transaction::class)
            ->willReturn($repositoryMock);

        // Should log the webhook processing and the skip message
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message, $context) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertSame('Processing order refund webhook', $message);
                    $this->assertArrayHasKey('refund_id', $context);
                    $this->assertSame('refund-123', $context['refund_id']);
                } else if ($callCount === 2) {
                    $this->assertSame('Refund transaction already exists, skipping', $message);
                    $this->assertArrayHasKey('transaction_id', $context);
                    $this->assertSame('txn-789', $context['transaction_id']);
                }

                return $this->logger;
            });

        // Should not look up shop or save transaction
        $this->shopAppInstallationRepository->expects($this->never())
            ->method('findOneByShopLicense');
            
        $this->transactionRepository->expects($this->never())
            ->method('save');

        // Act
        $this->service->processOrderRefundWebhook($webhookDto, $shopLicense);
    }
}
