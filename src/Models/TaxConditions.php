<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\TaxType;

class TaxConditions implements \JsonSerializable
{
    public function __construct(
        public readonly TaxType $taxType,
        public readonly ?string $taxTypeNote = null,
        public readonly ?string $taxSubType = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            taxType: TaxType::from($data['taxType']),
            taxTypeNote: $data['taxTypeNote'] ?? null,
            taxSubType: $data['taxSubType'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'taxType' => $this->taxType->value,
            'taxTypeNote' => $this->taxTypeNote,
            'taxSubType' => $this->taxSubType,
        ], static fn ($value) => $value !== null);
    }
}
