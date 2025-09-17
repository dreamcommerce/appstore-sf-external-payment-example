<?php

declare(strict_types=1);

namespace App\Dto\Webhook;

use Symfony\Component\Validator\Constraints as Assert;

class OrderTransactionWebhookDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Order ID cannot be empty')]
        #[Assert\Length(max: 255, maxMessage: 'Order ID cannot be longer than {{ limit }} characters')]
        public readonly string $orderId,

        #[Assert\NotBlank(message: 'Payment ID cannot be empty')]
        #[Assert\Length(max: 255, maxMessage: 'Payment ID cannot be longer than {{ limit }} characters')]
        public readonly string $paymentId,

        #[Assert\NotBlank(message: 'Transaction ID cannot be empty')]
        #[Assert\Length(max: 255, maxMessage: 'Transaction ID cannot be longer than {{ limit }} characters')]
        public readonly string $transactionId,

        #[Assert\NotBlank(message: 'Currency ID cannot be empty')]
        #[Assert\Length(max: 10, maxMessage: 'Currency ID cannot be longer than {{ limit }} characters')]
        #[Assert\Currency(message: 'Currency ID must be a valid currency code')]
        public readonly string $currencyId,

        #[Assert\NotBlank(message: 'Currency value cannot be empty')]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{1,2})?$/',
            message: 'Currency value must be a valid decimal number with up to 2 decimal places'
        )]
        public readonly string $currencyValue,

        #[Assert\Json(message: 'Payment data must be a valid JSON string')]
        public readonly ?string $paymentData = null,

        #[Assert\Url(message: 'Payment success shop link must be a valid URL')]
        #[Assert\Length(max: 500, maxMessage: 'Payment success shop link cannot be longer than {{ limit }} characters')]
        public readonly ?string $paymentSuccessShopLink = null,

        #[Assert\Url(message: 'Payment fail shop link must be a valid URL')]
        #[Assert\Length(max: 500, maxMessage: 'Payment fail shop link cannot be longer than {{ limit }} characters')]
        public readonly ?string $paymentFailShopLink = null
    ) {
    }

    /**
     * Creates DTO from array data
     *
     * @param array<string, mixed> $data Input data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $paymentData = null;
        if (isset($data['payment_data'])) {
            if (is_array($data['payment_data'])) {
                $paymentData = json_encode($data['payment_data']);
            } elseif (is_string($data['payment_data'])) {
                $paymentData = $data['payment_data'];
            }
        }
        return new self(
            orderId: (string) ($data['order_id'] ?? ''),
            paymentId: (string) ($data['payment_id'] ?? ''),
            transactionId: (string) ($data['transaction_id'] ?? ''),
            currencyId: (string) ($data['currency_id'] ?? ''),
            currencyValue: (string) ($data['currency_value'] ?? '0.00'),
            paymentData: $paymentData,
            paymentSuccessShopLink: isset($data['payment_success_shop_link']) ? (string) $data['payment_success_shop_link'] : null,
            paymentFailShopLink: isset($data['payment_fail_shop_link']) ? (string) $data['payment_fail_shop_link'] : null
        );
    }
}
