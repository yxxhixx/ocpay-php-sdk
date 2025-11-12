<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\DTO;

/**
 * Product information for payment link creation
 *
 * @property string $title Product/service name (1-200 characters)
 * @property string|null $description Detailed description (max 1000 characters, supports markdown)
 * @property int $amount Payment amount in DZD (500 - 500,000)
 */
class ProductInfo
{
    public function __construct(
        public readonly string $title,
        public readonly int $amount,
        public readonly ?string $description = null
    ) {
        $this->validate();
    }

    /**
     * Validate product info constraints
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->title) || strlen($this->title) > 200) {
            throw new \InvalidArgumentException(
                sprintf('Title must be between 1 and 200 characters, got %d', strlen($this->title))
            );
        }

        if ($this->description !== null && strlen($this->description) > 1000) {
            throw new \InvalidArgumentException(
                sprintf('Description must not exceed 1000 characters, got %d', strlen($this->description))
            );
        }

        // Check if amount is a whole number (no decimals)
        // Since amount is typed as int, this check is redundant but kept for clarity
        if ($this->amount !== (int) $this->amount) {
            throw new \InvalidArgumentException(
                sprintf('Amount must be a whole number (no decimals). Got: %s', (string) $this->amount)
            );
        }

        // Validate amount range with clear error messages
        if ($this->amount < 500) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Amount must be at least 500 DZD. Got: %d DZD. Please ensure your order total meets the minimum payment requirement.',
                    $this->amount
                )
            );
        }

        if ($this->amount > 500000) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Amount must not exceed 500,000 DZD. Got: %d DZD. Please split large orders into multiple payments.',
                    $this->amount
                )
            );
        }
    }

    /**
     * Create ProductInfo from a decimal amount (e.g., from database)
     * Automatically rounds to nearest whole number
     *
     * @param string $title
     * @param float|int|string $amount Decimal amount (will be rounded)
     * @param string|null $description
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromDecimalAmount(
        string $title,
        float|int|string $amount,
        ?string $description = null
    ): self {
        $amountFloat = (float) $amount;
        $amountInt = (int) round($amountFloat);

        // Warn if rounding occurred
        if (abs($amountFloat - $amountInt) > 0.01) {
            // Log warning but allow it (rounding is acceptable)
            // In production, you might want to log this
        }

        return new self($title, $amountInt, $description);
    }

    /**
     * Convert to array for API request
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'title' => $this->title,
            'amount' => $this->amount,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}

