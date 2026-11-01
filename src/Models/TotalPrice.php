<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class TotalPrice implements \JsonSerializable
{
    public function __construct(
        public readonly string $currency = 'EUR',
        public readonly ?float $totalDiscountAbsolute = null,
        public readonly ?float $totalDiscountPercentage = null,
        public readonly ?float $totalNetAmount = null,
        public readonly ?float $totalGrossAmount = null,
        public readonly ?float $totalTaxAmount = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currency: $data['currency'] ?? 'EUR',
            totalDiscountAbsolute: isset($data['totalDiscountAbsolute']) ? (float) $data['totalDiscountAbsolute'] : null,
            totalDiscountPercentage: isset($data['totalDiscountPercentage']) ? (float) $data['totalDiscountPercentage'] : null,
            totalNetAmount: isset($data['totalNetAmount']) ? (float) $data['totalNetAmount'] : null,
            totalGrossAmount: isset($data['totalGrossAmount']) ? (float) $data['totalGrossAmount'] : null,
            totalTaxAmount: isset($data['totalTaxAmount']) ? (float) $data['totalTaxAmount'] : null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'currency' => $this->currency,
            'totalDiscountAbsolute' => $this->totalDiscountAbsolute,
            'totalDiscountPercentage' => $this->totalDiscountPercentage,
        ], static fn ($value) => $value !== null);
    }
}
