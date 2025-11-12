<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Payment link information returned from API
 *
 * @property string $uid Merchant's unique identifier
 * @property string $ref Payment reference code (format: OCPL-XXXXXX-YYYY)
 * @property bool $isSandbox Whether this is a test payment link
 * @property ProductInfo $productInfo Product information as submitted
 * @property string $feeMode Fee configuration mode
 * @property string|null $successMessage Custom success message
 * @property string|null $redirectUrl Post-payment redirect URL
 * @property string $time Link creation timestamp (ISO 8601)
 */
class PaymentLink
{
    public function __construct(
        public readonly string $uid,
        public readonly string $ref,
        public readonly bool $isSandbox,
        public readonly ProductInfo $productInfo,
        public readonly string $feeMode,
        public readonly ?string $successMessage,
        public readonly ?string $redirectUrl,
        public readonly string $time
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
        $productInfoData = $data['productInfo'] ?? [];
        $productInfo = new ProductInfo(
            title: $productInfoData['title'] ?? '',
            amount: (int) ($productInfoData['amount'] ?? 0),
            description: $productInfoData['description'] ?? null
        );

        return new self(
            uid: $data['uid'] ?? '',
            ref: $data['ref'] ?? '',
            isSandbox: (bool) ($data['isSandbox'] ?? false),
            productInfo: $productInfo,
            feeMode: $data['feeMode'] ?? '',
            successMessage: $data['successMessage'] ?? null,
            redirectUrl: $data['redirectUrl'] ?? null,
            time: $data['time'] ?? ''
        );
    }
}

