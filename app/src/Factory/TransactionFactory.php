<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ShopPaymentMethod;
use App\Entity\Transaction;
use App\Service\Helper\DateTimeHelper;
use App\Service\Helper\JsonHelper;

class TransactionFactory
{
    public function __construct(
        private readonly JsonHelper $jsonHelper,
        private readonly DateTimeHelper $dateTimeHelper
    ) {
    }

    public function createOrderTransaction(
        ShopPaymentMethod $paymentMethod,
        string $transactionId,
        string $currencyId,
        string $currencyValue,
        ?string $orderId = null,
        ?string $paymentId = null,
        ?string $paymentData = null,
        ?string $paymentSuccessShopLink = null,
        ?string $paymentFailShopLink = null
    ): Transaction {
        $transaction = $this->createBaseTransaction(
            $paymentMethod,
            Transaction::TYPE_ORDER_TRANSACTION,
            $transactionId,
            $currencyId,
            $currencyValue
        );

        $transaction->setOrderId($orderId);
        $transaction->setExternalPaymentId($paymentId);

        if ($paymentData !== null) {
            $paymentDataArray = $this->jsonHelper->decodeToArray($paymentData, ['raw_data' => $paymentData]);
            $transaction->setPaymentData($paymentDataArray);
        }

        $transaction->setPaymentSuccessShopLink($paymentSuccessShopLink);
        $transaction->setPaymentFailShopLink($paymentFailShopLink);
        $transaction->setStatus('pending');
        $transaction->setTransactionDate(new \DateTimeImmutable());

        return $transaction;
    }

    public function createRefundTransaction(
        ShopPaymentMethod $paymentMethod,
        string $transactionId,
        string $currencyId,
        string $currencyValue,
        ?string $refundId = null,
        ?string $status = null,
        ?string $comment = null,
        ?string $date = null
    ): Transaction {
        $transaction = $this->createBaseTransaction(
            $paymentMethod,
            Transaction::TYPE_ORDER_REFUND,
            $transactionId,
            $currencyId,
            $currencyValue
        );

        $transaction->setRefundId($refundId);
        $transaction->setStatus($status ?? 'pending');
        $transaction->setComment($comment);
        $transaction->setTransactionDate($this->dateTimeHelper->createFromString($date));

        return $transaction;
    }

    private function createBaseTransaction(
        ShopPaymentMethod $paymentMethod,
        string $type,
        string $transactionId,
        string $currencyId,
        string $currencyValue
    ): Transaction {
        return new Transaction(
            $paymentMethod,
            $type,
            $transactionId,
            $currencyId,
            $currencyValue
        );
    }
}
