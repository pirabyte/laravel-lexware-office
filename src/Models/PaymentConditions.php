<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PaymentConditions implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $paymentTermLabel = null,
        public readonly ?int $paymentTermDuration = null,
        public readonly ?PaymentDiscountConditions $paymentDiscountConditions = null,
        public readonly ?string $paymentTermLabelTemplate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            paymentTermLabel: $data['paymentTermLabel'] ?? null,
            paymentTermDuration: $data['paymentTermDuration'] ?? null,
            paymentDiscountConditions: isset($data['paymentDiscountConditions']) ? PaymentDiscountConditions::fromArray($data['paymentDiscountConditions']) : null,
            paymentTermLabelTemplate: $data['paymentTermLabelTemplate'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'paymentTermLabel' => $this->paymentTermLabel,
            'paymentTermDuration' => $this->paymentTermDuration,
            'paymentDiscountConditions' => $this->paymentDiscountConditions,
        ], static fn ($value) => $value !== null);
    }
}
