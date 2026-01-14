<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\Contacts\EmailAddressCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\PhoneNumberCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class ContactWrite implements Dto
{
    public function __construct(
        public ContactRoles $roles,
        public ?Person $person,
        public ?Company $company,
        public ?string $note,
        public ContactAddresses $addresses,
        public EmailAddressCollection $emailAddresses,
        public PhoneNumberCollection $phoneNumbers,
        public ?int $version = null,
    ) {
        if ($this->person === null && $this->company === null) {
            throw new \InvalidArgumentException('ContactWrite must have either person or company');
        }

        if ($this->version !== null) {
            Assert::intRange($this->version, 0, PHP_INT_MAX, 'ContactWrite.version must be >= 0');
        }
    }
}


