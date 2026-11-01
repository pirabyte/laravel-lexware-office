<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PaymentItem implements \JsonSerializable
{
    public function __construct(
        public readonly string $paymentItemType,
        public readonly string $postingDate,
        public readonly float $amount,
        public readonly string $currency,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            paymentItemType: $data['paymentItemType'],
            postingDate: $data['postingDate'],
            amount: (float) $data['amount'],
            currency: $data['currency'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'paymentItemType' => $this->paymentItemType,
            'postingDate' => $this->postingDate,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ], static fn ($value) => $value !== null);
    }
}
