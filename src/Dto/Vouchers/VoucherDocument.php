<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Vouchers;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class VoucherDocument implements Dto
{
    public function __construct(
        public string $fileId,
        public ?string $fileName = null,
        public ?string $mimeType = null,
    ) {
        Assert::nonEmptyString($this->fileId, 'VoucherDocument.fileId must be non-empty');
    }
}


