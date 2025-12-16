<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Countries;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Enums\TaxClassification;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Country implements Dto
{
    public function __construct(
        public string $countryCode,
        public string $countryNameDE,
        public string $countryNameEN,
        public TaxClassification $taxClassification,
    ) {
        Assert::nonEmptyString($this->countryCode, 'Country.countryCode must be non-empty');
        Assert::nonEmptyString($this->countryNameDE, 'Country.countryNameDE must be non-empty');
        Assert::nonEmptyString($this->countryNameEN, 'Country.countryNameEN must be non-empty');
    }
}


