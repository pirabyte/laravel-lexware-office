<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Vouchers;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherFileCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherItemCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Voucher implements Dto
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $type,
        public ?string $voucherStatus,
        public ?string $voucherNumber,
        public DateTimeImmutable $voucherDate,
        public ?DateTimeImmutable $shippingDate,
        public ?DateTimeImmutable $dueDate,
        public float $totalGrossAmount,
        public ?float $totalTaxAmount,
        public string $taxType,
        public bool $useCollectiveContact,
        public ?string $remark,
        public VoucherItemCollection $voucherItems,
        public VoucherFileCollection $files,
        public ?DateTimeImmutable $createdDate,
        public ?DateTimeImmutable $updatedDate,
        public int $version,
    ) {
        Assert::nonEmptyString($this->id, 'Voucher.id must be non-empty');
        Assert::nonEmptyString($this->organizationId, 'Voucher.organizationId must be non-empty');
        Assert::nonEmptyString($this->type, 'Voucher.type must be non-empty');
        Assert::nonEmptyString($this->taxType, 'Voucher.taxType must be non-empty');
        Assert::intRange($this->version, 0, PHP_INT_MAX, 'Voucher.version must be >= 0');
    }
}


