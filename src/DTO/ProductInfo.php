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
            throw new \InvalidArgumentException('Title must be between 1 and 200 characters');
        }

        if ($this->description !== null && strlen($this->description) > 1000) {
            throw new \InvalidArgumentException('Description must not exceed 1000 characters');
        }

        if ($this->amount < 500 || $this->amount > 500000) {
            throw new \InvalidArgumentException('Amount must be between 500 and 500,000 DZD');
        }

        if ($this->amount !== (int) $this->amount) {
            throw new \InvalidArgumentException('Amount must be a whole number (no decimals)');
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
            'title' => $this->title,
            'amount' => $this->amount,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}

