<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\Contacts\EmailAddressCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\PhoneNumberCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Contact implements Dto
{
    public function __construct(
        public string $id,
        public int $version,
        public ContactRoles $roles,
        public ?Person $person,
        public ?Company $company,
        public ?string $note,
        public ContactAddresses $addresses,
        public EmailAddressCollection $emailAddresses,
        public PhoneNumberCollection $phoneNumbers,
    ) {
        Assert::nonEmptyString($this->id, 'Contact.id must be non-empty');
        Assert::intRange($this->version, 0, PHP_INT_MAX, 'Contact.version must be >= 0');

        if ($this->person === null && $this->company === null) {
            throw new \InvalidArgumentException('Contact must have either person or company');
        }
    }
}


