<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Vouchers;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherItemCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class VoucherWrite implements Dto
{
    public function __construct(
        public string $type,
        public DateTimeImmutable $voucherDate,
        public float $totalGrossAmount,
        public ?float $totalTaxAmount,
        public string $taxType,
        public bool $useCollectiveContact,
        public VoucherItemCollection $voucherItems,
        public ?DateTimeImmutable $shippingDate = null,
        public ?DateTimeImmutable $dueDate = null,
        public ?string $voucherNumber = null,
        public ?string $voucherStatus = null,
        public ?string $remark = null,
        public ?int $version = null,
        public ?string $contactId = null,
    ) {
        Assert::nonEmptyString($this->type, 'VoucherWrite.type must be non-empty');
        Assert::nonEmptyString($this->taxType, 'VoucherWrite.taxType must be non-empty');
        if (count($this->voucherItems) === 0) {
            throw new \InvalidArgumentException('VoucherWrite.voucherItems must not be empty');
        }
        if ($this->version !== null) {
            Assert::intRange($this->version, 0, PHP_INT_MAX, 'VoucherWrite.version must be >= 0');
        }
    }
}


