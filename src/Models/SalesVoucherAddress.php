<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class SalesVoucherAddress implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $contactId = null,
        public readonly ?string $name = null,
        public readonly ?string $supplement = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $zip = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $contactPerson = null,
    ) {}

    public static function fromContact(Contact $contact): self
    {
        return new self(contactId: $contact->getId());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            contactId: $data['contactId'] ?? null,
            name: $data['name'] ?? null,
            supplement: $data['supplement'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            contactPerson: $data['contactPerson'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'contactId' => $this->contactId,
            'name' => $this->name,
            'supplement' => $this->supplement,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->zip,
            'countryCode' => $this->countryCode,
        ], static fn ($value) => $value !== null);
    }
}
