<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\VoucherStatus;

class Payment
{
    /** @param list<PaymentItem> $paymentItems */
    public function __construct(
        public readonly float $openAmount,
        public readonly string $currency,
        public readonly string $paymentStatus,
        public readonly string $voucherType,
        public readonly VoucherStatus $voucherStatus,
        public readonly array $paymentItems,
        public readonly ?string $paidDate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (float) $data['openAmount'], $data['currency'], $data['paymentStatus'],
            $data['voucherType'], VoucherStatus::from($data['voucherStatus']),
            array_map(PaymentItem::fromArray(...), $data['paymentItems']), $data['paidDate'] ?? null,
        );
    }
}
