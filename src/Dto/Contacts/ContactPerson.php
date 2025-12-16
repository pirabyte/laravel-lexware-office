<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class ContactPerson implements Dto
{
    public function __construct(
        public string $lastName,
        public ?string $firstName = null,
        public ?string $emailAddress = null,
        public ?string $phoneNumber = null,
    ) {
        Assert::nonEmptyString($this->lastName, 'ContactPerson.lastName must be non-empty');
    }
}


