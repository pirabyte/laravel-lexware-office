<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Common;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Address implements Dto
{
    public function __construct(
        public string $street,
        public string $zip,
        public string $city,
        public string $countryCode,
        public ?string $supplement = null,
    ) {
        Assert::nonEmptyString($this->street, 'Address.street must be non-empty');
        Assert::nonEmptyString($this->zip, 'Address.zip must be non-empty');
        Assert::nonEmptyString($this->city, 'Address.city must be non-empty');
        Assert::nonEmptyString($this->countryCode, 'Address.countryCode must be non-empty');
    }
}


