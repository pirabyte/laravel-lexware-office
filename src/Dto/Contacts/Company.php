<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\Contacts\ContactPersonCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Company implements Dto
{
    public function __construct(
        public string $name,
        public ?string $taxNumber,
        public ?string $vatRegistrationId,
        public ?bool $allowTaxFreeInvoices,
        public ContactPersonCollection $contactPersons,
    ) {
        Assert::nonEmptyString($this->name, 'Company.name must be non-empty');
    }
}


