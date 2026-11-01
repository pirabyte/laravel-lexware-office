<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class UnitPrice implements \JsonSerializable
{
    public function __construct(
        public readonly string $currency = 'EUR',
        public readonly float $taxRatePercentage = 0,
        public readonly ?float $netAmount = null,
        public readonly ?float $grossAmount = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currency: $data['currency'] ?? 'EUR',
            taxRatePercentage: $data['taxRatePercentage'] ?? 0,
            netAmount: isset($data['netAmount']) ? (float) $data['netAmount'] : null,
            grossAmount: isset($data['grossAmount']) ? (float) $data['grossAmount'] : null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'currency' => $this->currency,
            'taxRatePercentage' => $this->taxRatePercentage,
            'netAmount' => $this->netAmount,
            'grossAmount' => $this->grossAmount,
        ], static fn ($value) => $value !== null);
    }
}
