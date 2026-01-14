<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class ApiError implements Dto
{
    public function __construct(
        public string $message,
        public string $type,
        public string $rawBody,
        public ?int $retryAfterSeconds = null,
    ) {
        Assert::nonEmptyString($this->message, 'ApiError.message must be non-empty');
        Assert::nonEmptyString($this->type, 'ApiError.type must be non-empty');
    }
}


