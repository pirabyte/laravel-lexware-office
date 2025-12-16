<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Vouchers;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class VoucherFile implements Dto
{
    public function __construct(
        public string $id,
        public ?string $fileName = null,
        public ?string $mimeType = null,
    ) {
        Assert::nonEmptyString($this->id, 'VoucherFile.id must be non-empty');
    }
}


