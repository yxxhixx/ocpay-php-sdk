<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Request DTO for creating a payment link
 *
 * @property ProductInfo $productInfo Product or service details being paid for
 * @property FeeMode $feeMode Determines who pays withdrawal fees
 * @property string|null $successMessage Custom success message (max 500 characters)
 * @property string|null $redirectUrl Redirect URL after successful payment
 */
class CreateLinkRequest
{
    /**
     * Fee mode options
     */
    public const FEE_MODE_NO_FEE = 'NO_FEE';
    public const FEE_MODE_SPLIT_FEE = 'SPLIT_FEE';
    public const FEE_MODE_CUSTOMER_FEE = 'CUSTOMER_FEE';

    public function __construct(
        public readonly ProductInfo $productInfo,
        public readonly string $feeMode = self::FEE_MODE_NO_FEE,
        public readonly ?string $successMessage = null,
        public readonly ?string $redirectUrl = null
    ) {
        $this->validate();
    }

    /**
     * Validate request constraints
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        $validFeeModes = [
            self::FEE_MODE_NO_FEE,
            self::FEE_MODE_SPLIT_FEE,
            self::FEE_MODE_CUSTOMER_FEE,
        ];

        if (!in_array($this->feeMode, $validFeeModes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid fee mode. Must be one of: %s', implode(', ', $validFeeModes))
            );
        }

        if ($this->successMessage !== null && strlen($this->successMessage) > 500) {
            throw new \InvalidArgumentException('Success message must not exceed 500 characters');
        }

        if ($this->redirectUrl !== null && !filter_var($this->redirectUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Redirect URL must be a valid HTTP/HTTPS URL');
        }
    }

    /**
     * Convert to array for API request
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'productInfo' => $this->productInfo->toArray(),
            'feeMode' => $this->feeMode,
        ];

        if ($this->successMessage !== null) {
            $data['successMessage'] = $this->successMessage;
        }

        if ($this->redirectUrl !== null) {
            $data['redirectUrl'] = $this->redirectUrl;
        }

        return $data;
    }
}

