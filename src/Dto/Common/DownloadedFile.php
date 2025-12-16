<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Common;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;

final readonly class DownloadedFile implements Dto
{
    public function __construct(
        public string $bytes,
        public ?string $contentType = null,
    ) {}
}


