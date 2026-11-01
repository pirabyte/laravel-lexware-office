<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class TaxAmount implements \JsonSerializable
{
    public function __construct(
        public readonly float $taxRatePercentage,
        public readonly float $taxAmount,
        public readonly float $netAmount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            taxRatePercentage: (float) $data['taxRatePercentage'],
            taxAmount: (float) $data['taxAmount'],
            netAmount: (float) $data['netAmount'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'taxRatePercentage' => $this->taxRatePercentage,
            'taxAmount' => $this->taxAmount,
            'netAmount' => $this->netAmount,
        ], static fn ($value) => $value !== null);
    }
}
