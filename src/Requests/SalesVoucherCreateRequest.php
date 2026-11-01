<?php

namespace Pirabyte\LaravelLexwareOffice\Requests;

use Pirabyte\LaravelLexwareOffice\Models\LineItem;
use Pirabyte\LaravelLexwareOffice\Models\SalesVoucherAddress;
use Pirabyte\LaravelLexwareOffice\Models\TaxConditions;
use Pirabyte\LaravelLexwareOffice\Models\TotalPrice;

abstract class SalesVoucherCreateRequest implements \JsonSerializable
{
    /** @param list<LineItem> $lineItems */
    public function __construct(
        public readonly string $voucherDate,
        public readonly SalesVoucherAddress $address,
        public readonly array $lineItems,
        public readonly TotalPrice $totalPrice,
        public readonly TaxConditions $taxConditions,
        public readonly ?string $language = null,
        public readonly ?string $printLayoutId = null,
        public readonly ?string $title = null,
        public readonly ?string $introduction = null,
        public readonly ?string $remark = null,
    ) {
        foreach ($lineItems as $item) {
            if (! $item instanceof LineItem) {
                throw new \InvalidArgumentException('lineItems must contain LineItem objects.');
            }
        }
    }

    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), static fn ($value) => $value !== null);
    }
}
