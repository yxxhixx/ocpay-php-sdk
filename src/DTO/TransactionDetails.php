<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Transaction details from payment status check
 *
 * @property int $amount Transaction amount
 * @property string $currency Currency code (typically "DZD")
 * @property bool $isSandbox Whether this is a test transaction
 * @property string $createdDate Transaction creation timestamp (ISO 8601)
 */
class TransactionDetails
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly bool $isSandbox,
        public readonly string $createdDate
    ) {
    }

    /**
     * Create from API response array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: (int) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'DZD',
            isSandbox: (bool) ($data['isSandbox'] ?? false),
            createdDate: $data['createdDate'] ?? ''
        );
    }
}

