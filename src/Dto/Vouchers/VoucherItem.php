<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Vouchers;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class VoucherItem implements Dto
{
    public function __construct(
        public float $amount,
        public float $taxAmount,
        public float $taxRatePercent,
        public string $categoryId,
    ) {
        Assert::nonEmptyString($this->categoryId, 'VoucherItem.categoryId must be non-empty');
    }
}


