<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class PhoneNumber implements Dto
{
    public function __construct(
        public PhoneNumberType $type,
        public string $number,
    ) {
        Assert::nonEmptyString($this->number, 'PhoneNumber.number must be non-empty');
    }
}


