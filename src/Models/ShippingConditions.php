<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\ShippingType;

class ShippingConditions implements \JsonSerializable
{
    public function __construct(
        public readonly ShippingType $shippingType,
        public readonly ?string $shippingDate = null,
        public readonly ?string $shippingEndDate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            shippingType: ShippingType::from($data['shippingType']),
            shippingDate: $data['shippingDate'] ?? null,
            shippingEndDate: $data['shippingEndDate'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'shippingType' => $this->shippingType->value,
            'shippingDate' => $this->shippingDate,
            'shippingEndDate' => $this->shippingEndDate,
        ], static fn ($value) => $value !== null);
    }
}
