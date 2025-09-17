<?php

declare(strict_types=1);

namespace App\Dto\Webhook;

use Symfony\Component\Validator\Constraints as Assert;

class OrderRefundWebhookDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Refund ID cannot be empty')]
        #[Assert\Length(max: 255, maxMessage: 'Refund ID cannot be longer than {{ limit }} characters')]
        public readonly string $refundId,

        #[Assert\NotBlank(message: 'Transaction ID cannot be empty')]
        #[Assert\Length(max: 255, maxMessage: 'Transaction ID cannot be longer than {{ limit }} characters')]
        public readonly string $transactionId,

        #[Assert\NotBlank(message: 'Status cannot be empty')]
        #[Assert\Length(max: 50, maxMessage: 'Status cannot be longer than {{ limit }} characters')]
        #[Assert\Choice(choices: ['pending', 'completed', 'failed', 'rejected'], message: 'Status must be one of: pending, completed, failed, rejected')]
        public readonly string $status,

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

        #[Assert\Length(max: 1000, maxMessage: 'Comment cannot be longer than {{ limit }} characters')]
        public readonly ?string $comment = null,

        #[Assert\Type(type: \DateTimeImmutable::class, message: 'Date must be a valid date')]
        public readonly ?\DateTimeImmutable $date = null
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
        $date = null;
        if (isset($data['date']) && !empty($data['date'])) {
            try {
                $date = new \DateTimeImmutable($data['date']);
            } catch (\Exception $e) {
                // Invalid date format, will be caught by validation
            }
        }

        return new self(
            refundId: (string) ($data['refund_id'] ?? ''),
            transactionId: (string) ($data['transaction_id'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            currencyId: (string) ($data['currency_id'] ?? ''),
            currencyValue: number_format((float)($data['currency_value'] ?? 0), 2, '.', ''),
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            date: $date
        );
    }
}
