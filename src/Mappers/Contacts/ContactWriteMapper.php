<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Common\Address;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactAddresses;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactPerson;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactRoles;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactWrite;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddress;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumber;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class ContactWriteMapper implements ApiMapper
{
    public static function toJsonBody(ContactWrite $contact): JsonBody
    {
        $payload = [];

        $roles = self::rolesPayload($contact->roles);
        if ($roles !== []) {
            $payload['roles'] = $roles;
        }

        if ($contact->person) {
            $payload['person'] = array_filter([
                'salutation' => $contact->person->salutation,
                'firstName' => $contact->person->firstName,
                'lastName' => $contact->person->lastName,
            ], static fn ($v) => $v !== null);
        }

        if ($contact->company) {
            $company = [
                'name' => $contact->company->name,
            ];

            if ($contact->company->taxNumber !== null) {
                $company['taxNumber'] = $contact->company->taxNumber;
            }
            if ($contact->company->vatRegistrationId !== null) {
                $company['vatRegistrationId'] = $contact->company->vatRegistrationId;
            }
            if ($contact->company->allowTaxFreeInvoices !== null) {
                $company['allowTaxFreeInvoices'] = $contact->company->allowTaxFreeInvoices;
            }

            if (count($contact->company->contactPersons) > 0) {
                $company['contactPersons'] = self::contactPersonsPayload($contact->company->contactPersons);
            }

            $payload['company'] = $company;
        }

        if ($contact->note !== null) {
            $payload['note'] = $contact->note;
        }

        $addresses = self::addressesPayload($contact->addresses);
        if ($addresses !== []) {
            $payload['addresses'] = $addresses;
        }

        $emailAddresses = self::emailAddressesPayload($contact->emailAddresses);
        if ($emailAddresses !== []) {
            $payload['emailAddresses'] = $emailAddresses;
        }

        $phoneNumbers = self::phoneNumbersPayload($contact->phoneNumbers);
        if ($phoneNumbers !== []) {
            $payload['phoneNumbers'] = $phoneNumbers;
        }

        if ($contact->version !== null) {
            $payload['version'] = $contact->version;
        }

        return new JsonBody(JsonCodec::encode($payload));
    }

    /**
     * @return array<string, mixed>
     */
    private static function rolesPayload(ContactRoles $roles): array
    {
        $payload = [];

        if ($roles->customer !== null) {
            $payload['customer'] = $roles->customer->number !== null
                ? ['number' => $roles->customer->number]
                : new \stdClass();
        }

        if ($roles->vendor !== null) {
            $payload['vendor'] = $roles->vendor->number !== null
                ? ['number' => $roles->vendor->number]
                : new \stdClass();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private static function addressesPayload(ContactAddresses $addresses): array
    {
        $payload = [];

        if ($addresses->billing !== null) {
            $payload['billing'] = [self::addressPayload($addresses->billing)];
        }

        if ($addresses->shipping !== null) {
            $payload['shipping'] = [self::addressPayload($addresses->shipping)];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private static function addressPayload(Address $address): array
    {
        $payload = [
            'street' => $address->street,
            'zip' => $address->zip,
            'city' => $address->city,
            'countryCode' => $address->countryCode,
        ];

        if ($address->supplement !== null) {
            $payload['supplement'] = $address->supplement;
        }

        return $payload;
    }

    /**
     * @param  iterable<ContactPerson>  $contactPersons
     * @return list<array<string, mixed>>
     */
    private static function contactPersonsPayload(iterable $contactPersons): array
    {
        $payload = [];
        foreach ($contactPersons as $person) {
            $payload[] = array_filter([
                'lastName' => $person->lastName,
                'firstName' => $person->firstName,
                'emailAddress' => $person->emailAddress,
                'phoneNumber' => $person->phoneNumber,
            ], static fn ($v) => $v !== null);
        }

        return $payload;
    }

    /**
     * @param  iterable<EmailAddress>  $emailAddresses
     * @return array<string, list<string>>
     */
    private static function emailAddressesPayload(iterable $emailAddresses): array
    {
        $payload = [];
        foreach ($emailAddresses as $email) {
            $payload[$email->type->value] = [$email->email];
        }

        return $payload;
    }

    /**
     * @param  iterable<PhoneNumber>  $phoneNumbers
     * @return array<string, list<string>>
     */
    private static function phoneNumbersPayload(iterable $phoneNumbers): array
    {
        $payload = [];
        foreach ($phoneNumbers as $phone) {
            $payload[$phone->type->value] = [$phone->number];
        }

        return $payload;
    }
}


