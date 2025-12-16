<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Common;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class KeyValue implements Dto
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
        Assert::nonEmptyString($this->key, 'KeyValue.key must be non-empty');
    }
}


