<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class JsonBody
{
    public function __construct(public string $json)
    {
        Assert::nonEmptyString($this->json, 'JsonBody.json must be non-empty');
    }
}


