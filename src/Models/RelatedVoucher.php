<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class RelatedVoucher implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $voucherNumber,
        public readonly string $voucherType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            voucherNumber: $data['voucherNumber'],
            voucherType: $data['voucherType'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'voucherNumber' => $this->voucherNumber,
            'voucherType' => $this->voucherType,
        ], static fn ($value) => $value !== null);
    }
}
