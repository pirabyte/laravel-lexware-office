<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\LineItemType;

class LineItem implements \JsonSerializable
{
    public function __construct(
        public readonly LineItemType $type,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?float $quantity = null,
        public readonly ?string $unitName = null,
        public readonly ?UnitPrice $unitPrice = null,
        public readonly ?float $discountPercentage = null,
        public readonly ?string $id = null,
        public readonly ?float $lineItemAmount = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: LineItemType::from($data['type']),
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            unitName: $data['unitName'] ?? null,
            unitPrice: isset($data['unitPrice']) ? UnitPrice::fromArray($data['unitPrice']) : null,
            discountPercentage: isset($data['discountPercentage']) ? (float) $data['discountPercentage'] : null,
            id: $data['id'] ?? null,
            lineItemAmount: isset($data['lineItemAmount']) ? (float) $data['lineItemAmount'] : null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unitName' => $this->unitName,
            'unitPrice' => $this->unitPrice,
            'discountPercentage' => $this->discountPercentage,
            'id' => $this->id,
        ], static fn ($value) => $value !== null);
    }
}
