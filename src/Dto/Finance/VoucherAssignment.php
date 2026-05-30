<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class VoucherAssignment implements Dto
{
    public function __construct(
        public string $id,
        public string $type,
    ) {
        Assert::nonEmptyString($this->id, 'VoucherAssignment.id must be non-empty');
        Assert::nonEmptyString($this->type, 'VoucherAssignment.type must be non-empty');
    }
}


