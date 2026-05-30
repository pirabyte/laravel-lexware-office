<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class QueryParam
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
        Assert::nonEmptyString($this->key, 'QueryParam.key must be non-empty');
    }
}


