<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PaymentDiscountConditions implements \JsonSerializable
{
    public function __construct(
        public readonly float $discountPercentage,
        public readonly int $discountRange,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            discountPercentage: (float) $data['discountPercentage'],
            discountRange: $data['discountRange'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'discountPercentage' => $this->discountPercentage,
            'discountRange' => $this->discountRange,
        ], static fn ($value) => $value !== null);
    }
}
