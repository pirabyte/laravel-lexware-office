<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\ContactPersonCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\EmailAddressCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\PhoneNumberCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Common\Address;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Company;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactAddresses;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactRoles;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactWrite;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\CustomerRole;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddress;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddressType;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Person;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumber;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumberType;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\VendorRole;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

/**
 * @method assertEquals(string $string, string $getId)
 */
class ContactApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock-Responses für die API-Aufrufe bei Personen-Kontakt
        $personMockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/person_contact_create_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/person_contact_get_response.json')),
        ];

        $mock = new MockHandler($personMockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    /** @test */
    public function it_can_create_and_retrieve_a_person_contact(): void
    {
        // Kontakt erstellen
        $contact = new ContactWrite(
            roles: new ContactRoles(customer: new CustomerRole('K-12345'), vendor: null),
            person: new Person(salutation: 'Herr', firstName: 'Max', lastName: 'Mustermann'),
            company: null,
            note: null,
            addresses: new ContactAddresses(billing: null, shipping: null),
            emailAddresses: EmailAddressCollection::empty(),
            phoneNumbers: PhoneNumberCollection::empty(),
        );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $savedContact->id);
        $this->assertEquals(1, $savedContact->version);
        $this->assertEquals('Herr', $savedContact->person?->salutation);
        $this->assertEquals('Max', $savedContact->person?->firstName);
        $this->assertEquals('Mustermann', $savedContact->person?->lastName);
        $this->assertEquals('K-12345', $savedContact->roles->customer?->number);
    }

    /** @test */
    public function it_can_create_a_person_contact_with_complete_data(): void
    {
        // Prepare mock responses for complete contact data creation
        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/complete_person_contact_create_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/complete_person_contact_get_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        $contact = new ContactWrite(
            roles: new ContactRoles(customer: new CustomerRole(null), vendor: null),
            person: new Person(salutation: 'Frau', firstName: 'Inge', lastName: 'Musterfrau'),
            company: null,
            note: 'Notizen',
            addresses: new ContactAddresses(
                billing: new Address(street: 'Musterstraße 1', zip: '12345', city: 'Musterstadt', countryCode: 'DE'),
                shipping: null
            ),
            emailAddresses: EmailAddressCollection::empty()->with(new EmailAddress(EmailAddressType::BUSINESS, 'inge@example.com')),
            phoneNumbers: PhoneNumberCollection::empty()->with(new PhoneNumber(PhoneNumberType::BUSINESS, '+49123456789')),
        );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('66196c43-baf3-4335-bfee-d610367059db', $savedContact->id);
        $this->assertEquals('Frau', $savedContact->person?->salutation);
        $this->assertEquals('Inge', $savedContact->person?->firstName);
        $this->assertEquals('Musterfrau', $savedContact->person?->lastName);
        $this->assertEquals('Notizen', $savedContact->note);
        $this->assertEquals('Musterstraße 1', $savedContact->addresses->billing?->street);
    }

    /** @test */
    public function it_can_handle_multiple_email_and_phone_types(): void
    {
        // Prepare mock responses for complete contact data creation
        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/multiple_email_phone_create_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/multiple_email_phone_get_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        $contact = new ContactWrite(
            roles: new ContactRoles(customer: new CustomerRole(null), vendor: null),
            person: new Person(salutation: 'Herr', firstName: 'Hans', lastName: 'Schmidt'),
            company: null,
            note: null,
            addresses: new ContactAddresses(billing: null, shipping: null),
            emailAddresses: EmailAddressCollection::empty()
                ->with(new EmailAddress(EmailAddressType::BUSINESS, 'hans.business@example.com'))
                ->with(new EmailAddress(EmailAddressType::PRIVATE, 'hans.private@example.com'))
                ->with(new EmailAddress(EmailAddressType::OFFICE, 'hans.office@example.com')),
            phoneNumbers: PhoneNumberCollection::empty()
                ->with(new PhoneNumber(PhoneNumberType::BUSINESS, '+4912345678901'))
                ->with(new PhoneNumber(PhoneNumberType::MOBILE, '+4915123456789'))
                ->with(new PhoneNumber(PhoneNumberType::FAX, '+49123456789999')),
        );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('12345678-abcd-1234-efgh-123456789012', $savedContact->id);

        $this->assertCount(3, $savedContact->emailAddresses);
        $this->assertEquals('hans.business@example.com', $savedContact->emailAddresses->getByType(EmailAddressType::BUSINESS)?->email);
        $this->assertEquals('hans.private@example.com', $savedContact->emailAddresses->getByType(EmailAddressType::PRIVATE)?->email);
        $this->assertEquals('hans.office@example.com', $savedContact->emailAddresses->getByType(EmailAddressType::OFFICE)?->email);
        $this->assertNull($savedContact->emailAddresses->getByType(EmailAddressType::OTHER));

        $this->assertCount(3, $savedContact->phoneNumbers);
        $this->assertEquals('+4912345678901', $savedContact->phoneNumbers->getByType(PhoneNumberType::BUSINESS)?->number);
        $this->assertEquals('+4915123456789', $savedContact->phoneNumbers->getByType(PhoneNumberType::MOBILE)?->number);
        $this->assertEquals('+49123456789999', $savedContact->phoneNumbers->getByType(PhoneNumberType::FAX)?->number);
        $this->assertNull($savedContact->phoneNumbers->getByType(PhoneNumberType::PRIVATE));
    }

    /** @test */
    public function it_can_handle_multiple_address_types(): void
    {
        // Prepare mock responses for contact with multiple address types
        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/multiple_address_types_create_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/multiple_address_types_get_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        $contact = new ContactWrite(
            roles: new ContactRoles(customer: new CustomerRole(null), vendor: null),
            person: new Person(salutation: 'Herr', firstName: 'Peter', lastName: 'Beispiel'),
            company: null,
            note: null,
            addresses: new ContactAddresses(
                billing: new Address(street: 'Hauptstr. 5', zip: '12345', city: 'Musterort', countryCode: 'DE', supplement: 'Rechnungsadressenzusatz'),
                shipping: new Address(street: 'Schulstr. 13', zip: '76543', city: 'Musterstadt', countryCode: 'DE', supplement: 'Lieferadressenzusatz'),
            ),
            emailAddresses: EmailAddressCollection::empty(),
            phoneNumbers: PhoneNumberCollection::empty(),
        );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('44444444-bbbb-4444-cccc-444444444444', $savedContact->id);

        $billingAddress = $savedContact->addresses->billing;
        $this->assertNotNull($billingAddress);
        $this->assertEquals('Hauptstr. 5', $billingAddress->street);
        $this->assertEquals('12345', $billingAddress->zip);
        $this->assertEquals('Musterort', $billingAddress->city);
        $this->assertEquals('DE', $billingAddress->countryCode);
        $this->assertEquals('Rechnungsadressenzusatz', $billingAddress->supplement);

        $shippingAddress = $savedContact->addresses->shipping;
        $this->assertNotNull($shippingAddress);
        $this->assertEquals('Schulstr. 13', $shippingAddress->street);
        $this->assertEquals('76543', $shippingAddress->zip);
        $this->assertEquals('Musterstadt', $shippingAddress->city);
        $this->assertEquals('DE', $shippingAddress->countryCode);
        $this->assertEquals('Lieferadressenzusatz', $shippingAddress->supplement);
    }

    /** @test */
    public function it_can_create_a_company_contact(): void
    {
        // Prepare mock responses for company contact creation
        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/company_contact_create_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/contacts/api_responses/company_contact_get_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        $contact = new ContactWrite(
            roles: new ContactRoles(customer: null, vendor: new VendorRole('L-789')),
            person: null,
            company: new Company(
                name: 'Musterfirma GmbH',
                taxNumber: null,
                vatRegistrationId: null,
                allowTaxFreeInvoices: false,
                contactPersons: ContactPersonCollection::empty(),
            ),
            note: null,
            addresses: new ContactAddresses(
                billing: new Address(street: 'Industriestraße 42', zip: '54321', city: 'Musterstadt', countryCode: 'DE'),
                shipping: null,
            ),
            emailAddresses: EmailAddressCollection::empty(),
            phoneNumbers: PhoneNumberCollection::empty(),
        );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('87654321-abcd-1234-efgh-987654321987', $savedContact->id);
        $this->assertEquals('Musterfirma GmbH', $savedContact->company?->name);
        $this->assertEquals('L-789', $savedContact->roles->vendor?->number);
        $this->assertEquals('DE', $savedContact->addresses->billing?->countryCode);
    }
}
