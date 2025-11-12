<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Payment status values
 */
enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case FAILED = 'FAILED';
}

/**
 * Response DTO for payment status check
 *
 * @property PaymentStatus $status Payment status (PENDING, CONFIRMED, or FAILED)
 * @property string $message Status message
 * @property string $paymentRef Payment reference code
 * @property TransactionDetails|null $transactionDetails Transaction details (if available)
 */
class CheckPaymentResponse
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly string $message,
        public readonly string $paymentRef,
        public readonly ?TransactionDetails $transactionDetails = null
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
        $status = PaymentStatus::from($data['status'] ?? 'PENDING');
        $transactionDetails = null;

        if (isset($data['transactionDetails'])) {
            $transactionDetails = TransactionDetails::fromArray($data['transactionDetails']);
        }

        return new self(
            status: $status,
            message: $data['message'] ?? '',
            paymentRef: $data['paymentRef'] ?? '',
            transactionDetails: $transactionDetails
        );
    }

    /**
     * Check if payment is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::CONFIRMED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }
}

