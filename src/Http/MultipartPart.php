<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Support\Assert;
use Psr\Http\Message\StreamInterface;

final readonly class MultipartPart
{
    public function __construct(
        public string $name,
        public string|StreamInterface $contents,
        public ?string $filename = null,
    ) {
        Assert::nonEmptyString($this->name, 'MultipartPart.name must be non-empty');
    }
}


