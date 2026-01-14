<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\Contacts\ContactPersonCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\EmailAddressCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\PhoneNumberCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Common\Address;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Company;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Contact;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactAddresses;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactPerson;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactRoles;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\CustomerRole;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddress;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddressType;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Person;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumber;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumberType;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\VendorRole;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class ContactMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): Contact
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for Contact', $rawJson);
        }

        /** @var array<string, mixed> $data */
        return self::fromArray($data, $rawJson);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $rawJson): Contact
    {
        $roles = self::roles($data['roles'] ?? null, $rawJson);
        $person = self::person($data['person'] ?? null, $rawJson);
        $company = self::company($data['company'] ?? null, $rawJson);
        $addresses = self::addresses($data['addresses'] ?? null, $rawJson);
        $emails = self::emailAddresses($data['emailAddresses'] ?? null, $rawJson);
        $phones = self::phoneNumbers($data['phoneNumbers'] ?? null, $rawJson);

        return new Contact(
            id: Assert::string($data['id'] ?? null, 'Contact.id missing'),
            version: Assert::int($data['version'] ?? 0, 'Contact.version must be int'),
            roles: $roles,
            person: $person,
            company: $company,
            note: Assert::nullableString($data['note'] ?? null, 'Contact.note must be string|null'),
            addresses: $addresses,
            emailAddresses: $emails,
            phoneNumbers: $phones,
        );
    }

    private static function roles(mixed $roles, string $rawJson): ContactRoles
    {
        if ($roles === null) {
            return new ContactRoles(null, null);
        }

        $roles = Assert::array($roles, 'Contact.roles must be an object');
        if (array_is_list($roles)) {
            throw new DecodeException('Contact.roles must be an object', $rawJson);
        }

        /** @var array<string, mixed> $roles */
        $customer = null;
        if (array_key_exists('customer', $roles)) {
            $customer = new CustomerRole(self::roleNumber($roles['customer'], $rawJson));
        }

        $vendor = null;
        if (array_key_exists('vendor', $roles)) {
            $vendor = new VendorRole(self::roleNumber($roles['vendor'], $rawJson));
        }

        return new ContactRoles($customer, $vendor);
    }

    private static function roleNumber(mixed $role, string $rawJson): ?string
    {
        if ($role === null) {
            return null;
        }

        // Role can be {} or [] depending on API usage.
        if (! is_array($role)) {
            return null;
        }

        if (array_is_list($role)) {
            return null;
        }

        /** @var array<string, mixed> $role */
        return Assert::nullableString($role['number'] ?? null, 'Role.number must be string|null');
    }

    private static function person(mixed $person, string $rawJson): ?Person
    {
        if ($person === null) {
            return null;
        }

        $person = Assert::array($person, 'Contact.person must be an object');
        if (array_is_list($person)) {
            throw new DecodeException('Contact.person must be an object', $rawJson);
        }

        /** @var array<string, mixed> $person */
        return new Person(
            salutation: Assert::nullableString($person['salutation'] ?? null, 'Person.salutation must be string|null'),
            firstName: Assert::nullableString($person['firstName'] ?? null, 'Person.firstName must be string|null'),
            lastName: Assert::string($person['lastName'] ?? null, 'Person.lastName missing'),
        );
    }

    private static function company(mixed $company, string $rawJson): ?Company
    {
        if ($company === null) {
            return null;
        }

        $company = Assert::array($company, 'Contact.company must be an object');
        if (array_is_list($company)) {
            throw new DecodeException('Contact.company must be an object', $rawJson);
        }

        /** @var array<string, mixed> $company */
        $persons = ContactPersonCollection::empty();
        $contactPersons = $company['contactPersons'] ?? [];
        $contactPersons = Assert::array($contactPersons, 'Company.contactPersons must be a list');
        if (! array_is_list($contactPersons)) {
            throw new DecodeException('Company.contactPersons must be a list', $rawJson);
        }

        foreach ($contactPersons as $row) {
            $row = Assert::array($row, 'Company.contactPersons item must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('Company.contactPersons item must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */
            $persons = $persons->with(new ContactPerson(
                lastName: Assert::string($row['lastName'] ?? null, 'ContactPerson.lastName missing'),
                firstName: Assert::nullableString($row['firstName'] ?? null, 'ContactPerson.firstName must be string|null'),
                emailAddress: Assert::nullableString($row['emailAddress'] ?? null, 'ContactPerson.emailAddress must be string|null'),
                phoneNumber: Assert::nullableString($row['phoneNumber'] ?? null, 'ContactPerson.phoneNumber must be string|null'),
            ));
        }

        return new Company(
            name: Assert::string($company['name'] ?? null, 'Company.name missing'),
            taxNumber: Assert::nullableString($company['taxNumber'] ?? null, 'Company.taxNumber must be string|null'),
            vatRegistrationId: Assert::nullableString($company['vatRegistrationId'] ?? null, 'Company.vatRegistrationId must be string|null'),
            allowTaxFreeInvoices: ($company['allowTaxFreeInvoices'] ?? null) === null
                ? null
                : Assert::bool($company['allowTaxFreeInvoices'], 'Company.allowTaxFreeInvoices must be bool|null'),
            contactPersons: $persons,
        );
    }

    private static function addresses(mixed $addresses, string $rawJson): ContactAddresses
    {
        if ($addresses === null) {
            return new ContactAddresses(null, null);
        }

        $addresses = Assert::array($addresses, 'Contact.addresses must be an object');
        if (array_is_list($addresses)) {
            throw new DecodeException('Contact.addresses must be an object', $rawJson);
        }

        /** @var array<string, mixed> $addresses */
        $billing = self::firstAddress($addresses['billing'] ?? null, $rawJson);
        $shipping = self::firstAddress($addresses['shipping'] ?? null, $rawJson);

        return new ContactAddresses($billing, $shipping);
    }

    private static function firstAddress(mixed $value, string $rawJson): ?Address
    {
        if ($value === null) {
            return null;
        }

        $list = Assert::array($value, 'Address list must be a list');
        if (! array_is_list($list) || $list === []) {
            return null;
        }

        $row = Assert::array($list[0], 'Address entry must be an object');
        if (array_is_list($row)) {
            throw new DecodeException('Address entry must be an object', $rawJson);
        }

        /** @var array<string, mixed> $row */
        return new Address(
            street: Assert::string($row['street'] ?? null, 'Address.street missing'),
            zip: Assert::string($row['zip'] ?? null, 'Address.zip missing'),
            city: Assert::string($row['city'] ?? null, 'Address.city missing'),
            countryCode: Assert::string($row['countryCode'] ?? null, 'Address.countryCode missing'),
            supplement: Assert::nullableString($row['supplement'] ?? null, 'Address.supplement must be string|null'),
        );
    }

    private static function emailAddresses(mixed $value, string $rawJson): EmailAddressCollection
    {
        if ($value === null) {
            return EmailAddressCollection::empty();
        }

        $map = Assert::array($value, 'Contact.emailAddresses must be an object');
        if (array_is_list($map)) {
            throw new DecodeException('Contact.emailAddresses must be an object', $rawJson);
        }

        /** @var array<string, mixed> $map */
        $collection = EmailAddressCollection::empty();
        foreach ($map as $type => $emails) {
            if (! is_string($type) || $type === '') {
                continue;
            }

            try {
                $enum = EmailAddressType::from($type);
            } catch (\ValueError) {
                // Ignore unknown types for forward compatibility.
                continue;
            }

            $emails = Assert::array($emails, 'Contact.emailAddresses values must be lists');
            if (! array_is_list($emails) || $emails === []) {
                continue;
            }

            $email = $emails[0];
            if (! is_string($email) || $email === '') {
                continue;
            }

            $collection = $collection->with(new EmailAddress($enum, $email));
        }

        return $collection;
    }

    private static function phoneNumbers(mixed $value, string $rawJson): PhoneNumberCollection
    {
        if ($value === null) {
            return PhoneNumberCollection::empty();
        }

        $map = Assert::array($value, 'Contact.phoneNumbers must be an object');
        if (array_is_list($map)) {
            throw new DecodeException('Contact.phoneNumbers must be an object', $rawJson);
        }

        /** @var array<string, mixed> $map */
        $collection = PhoneNumberCollection::empty();
        foreach ($map as $type => $numbers) {
            if (! is_string($type) || $type === '') {
                continue;
            }

            try {
                $enum = PhoneNumberType::from($type);
            } catch (\ValueError) {
                // Ignore unknown types for forward compatibility.
                continue;
            }

            $numbers = Assert::array($numbers, 'Contact.phoneNumbers values must be lists');
            if (! array_is_list($numbers) || $numbers === []) {
                continue;
            }

            $number = $numbers[0];
            if (! is_string($number) || $number === '') {
                continue;
            }

            $collection = $collection->with(new PhoneNumber($enum, $number));
        }

        return $collection;
    }
}


