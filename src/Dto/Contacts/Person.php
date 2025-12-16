<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Person implements Dto
{
    public function __construct(
        public ?string $salutation,
        public ?string $firstName,
        public string $lastName,
    ) {
        Assert::nonEmptyString($this->lastName, 'Person.lastName must be non-empty');
    }
}


