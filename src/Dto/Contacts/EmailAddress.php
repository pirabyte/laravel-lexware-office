<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class EmailAddress implements Dto
{
    public function __construct(
        public EmailAddressType $type,
        public string $email,
    ) {
        Assert::nonEmptyString($this->email, 'EmailAddress.email must be non-empty');
    }
}


