<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Response DTO for payment link creation
 *
 * @property PaymentLink $paymentLink Complete payment link information
 * @property string $paymentUrl Complete URL to share with customers for payment
 * @property string $paymentRef Payment reference code - SAVE THIS for tracking payments
 */
class CreateLinkResponse
{
    public function __construct(
        public readonly PaymentLink $paymentLink,
        public readonly string $paymentUrl,
        public readonly string $paymentRef
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
        $paymentLink = PaymentLink::fromArray($data['paymentLink'] ?? []);

        return new self(
            paymentLink: $paymentLink,
            paymentUrl: $data['paymentUrl'] ?? '',
            paymentRef: $data['paymentRef'] ?? ''
        );
    }
}

