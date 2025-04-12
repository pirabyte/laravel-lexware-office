<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
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
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'version' => 1,
                'roles' => [
                    'customer' => ['number' => 'K-12345'],
                ],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                ],
            ])),
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
        $contact = Contact::createPerson('Max', 'Mustermann', 'Herr');
        $contact->setAsCustomer(['number' => 'K-12345']);

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $savedContact->getId());
        $this->assertEquals(1, $savedContact->getVersion());
        $this->assertEquals('Herr', $savedContact->getPerson()->getSalutation());
        $this->assertEquals('Max', $savedContact->getPerson()->getFirstName());
        $this->assertEquals('Mustermann', $savedContact->getPerson()->getLastName());
        $this->assertEquals('K-12345', $savedContact->getRoles()['customer']['number']);
    }

    /** @test */
    public function it_can_create_a_person_contact_with_complete_data(): void
    {
        // Prepare mock responses for complete contact data creation
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '66196c43-baf3-4335-bfee-d610367059db',
                'resourceUri' => 'https://api-sandbox.grld.eu/v1/contacts/66196c43-baf3-4335-bfee-d610367059db',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '66196c43-baf3-4335-bfee-d610367059db',
                'version' => 1,
                'roles' => ['customer' => []],
                'person' => [
                    'salutation' => 'Frau',
                    'firstName' => 'Inge',
                    'lastName' => 'Musterfrau',
                ],
                'note' => 'Notizen',
                'addresses' => [
                    ['billing' => [
                        [
                            'street' => 'Musterstraße 1',
                            'zip' => '12345',
                            'city' => 'Musterstadt',
                            'countryCode' => 'DE',
                        ],
                    ]],
                ],
                'emailAddresses' => [
                    ['business' => ['inge@example.com']],
                ],
                'phoneNumbers' => [
                    ['business' => ['+49123456789']],
                ],
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Kontakt mit allen Daten erstellen
        $contact = Contact::createPerson('Inge', 'Musterfrau', 'Frau');
        $contact->setAsCustomer()
            ->setNote('Notizen')
            ->addBillingAddress(
                'Musterstraße 1',
                '12345',
                'Musterstadt',
                'DE'
            )
            ->addEmailAddress('inge@example.com', 'business')
            ->addPhoneNumber('+49123456789', 'business');

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('66196c43-baf3-4335-bfee-d610367059db', $savedContact->getId());
        $this->assertEquals('Frau', $savedContact->getPerson()->getSalutation());
        $this->assertEquals('Inge', $savedContact->getPerson()->getFirstName());
        $this->assertEquals('Musterfrau', $savedContact->getPerson()->getLastName());
        $this->assertEquals('Notizen', $savedContact->getNote());
    }

    /** @test */
    public function it_can_handle_multiple_email_and_phone_types(): void
    {
        // Prepare mock responses for complete contact data creation
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '12345678-abcd-1234-efgh-123456789012',
                'resourceUri' => 'https://api-sandbox.grld.eu/v1/contacts/12345678-abcd-1234-efgh-123456789012',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '12345678-abcd-1234-efgh-123456789012',
                'version' => 1,
                'roles' => ['customer' => []],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Hans',
                    'lastName' => 'Schmidt',
                ],
                'emailAddresses' => [
                    ['business' => ['hans.business@example.com']],
                    ['private' => ['hans.private@example.com']],
                    ['office' => ['hans.office@example.com']],
                ],
                'phoneNumbers' => [
                    ['business' => ['+4912345678901']],
                    ['mobile' => ['+4915123456789']],
                    ['fax' => ['+49123456789999']],
                ],
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Kontakt mit verschiedenen E-Mail und Telefonnummern erstellen
        $contact = Contact::createPerson('Hans', 'Schmidt', 'Herr');
        $contact->setAsCustomer()
            ->addEmailAddress('hans.business@example.com', 'business')
            ->addEmailAddress('hans.private@example.com', 'private')
            ->addEmailAddress('hans.office@example.com', 'office')
            ->addPhoneNumber('+4912345678901', 'business')
            ->addPhoneNumber('+4915123456789', 'mobile')
            ->addPhoneNumber('+49123456789999', 'fax');

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('12345678-abcd-1234-efgh-123456789012', $savedContact->getId());

        // Test email addresses using the collection
        $emailAddresses = $savedContact->getEmailAddresses();
        $this->assertCount(3, $emailAddresses);

        // Test individual email getters
        $this->assertEquals('hans.business@example.com', $savedContact->getEmailAddress('business'));
        $this->assertEquals('hans.private@example.com', $savedContact->getEmailAddress('private'));
        $this->assertEquals('hans.office@example.com', $savedContact->getEmailAddress('office'));
        $this->assertNull($savedContact->getEmailAddress('other'));

        // Test phone numbers using the collection
        $phoneNumbers = $savedContact->getPhoneNumbers();
        $this->assertCount(3, $phoneNumbers);

        // Test individual phone getters
        $this->assertEquals('+4912345678901', $savedContact->getPhoneNumber('business'));
        $this->assertEquals('+4915123456789', $savedContact->getPhoneNumber('mobile'));
        $this->assertEquals('+49123456789999', $savedContact->getPhoneNumber('fax'));
        $this->assertNull($savedContact->getPhoneNumber('private'));
    }

    /** @test */
    public function it_can_handle_multiple_address_types(): void
    {
        // Prepare mock responses for contact with multiple address types
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '44444444-bbbb-4444-cccc-444444444444',
                'resourceUri' => 'https://api-sandbox.grld.eu/v1/contacts/44444444-bbbb-4444-cccc-444444444444',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '44444444-bbbb-4444-cccc-444444444444',
                'version' => 1,
                'roles' => ['customer' => []],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Peter',
                    'lastName' => 'Beispiel',
                ],
                'addresses' => [
                    ['billing' => [
                        [
                            'supplement' => 'Rechnungsadressenzusatz',
                            'street' => 'Hauptstr. 5',
                            'zip' => '12345',
                            'city' => 'Musterort',
                            'countryCode' => 'DE',
                        ],
                    ]],
                    ['shipping' => [
                        [
                            'supplement' => 'Lieferadressenzusatz',
                            'street' => 'Schulstr. 13',
                            'zip' => '76543',
                            'city' => 'Musterstadt',
                            'countryCode' => 'DE',
                        ],
                    ]],
                ],
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Kontakt mit verschiedenen Adresstypen erstellen
        $contact = Contact::createPerson('Peter', 'Beispiel', 'Herr');
        $contact->setAsCustomer()
            ->addBillingAddress(
                'Hauptstr. 5',
                '12345',
                'Musterort',
                'DE',
                'Rechnungsadressenzusatz'
            )
            ->addShippingAddress(
                'Schulstr. 13',
                '76543',
                'Musterstadt',
                'DE',
                'Lieferadressenzusatz'
            );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        // Prüfen der Ergebnisse
        $this->assertEquals('44444444-bbbb-4444-cccc-444444444444', $savedContact->getId());

        // Billing address test
        $billingAddress = $savedContact->getAddress('billing');
        $this->assertNotNull($billingAddress);
        $this->assertEquals('Hauptstr. 5', $billingAddress['street']);
        $this->assertEquals('12345', $billingAddress['zip']);
        $this->assertEquals('Musterort', $billingAddress['city']);
        $this->assertEquals('DE', $billingAddress['countryCode']);
        $this->assertEquals('Rechnungsadressenzusatz', $billingAddress['supplement']);

        // Shipping address test
        $shippingAddress = $savedContact->getAddress('shipping');
        $this->assertNotNull($shippingAddress);
        $this->assertEquals('Schulstr. 13', $shippingAddress['street']);
        $this->assertEquals('76543', $shippingAddress['zip']);
        $this->assertEquals('Musterstadt', $shippingAddress['city']);
        $this->assertEquals('DE', $shippingAddress['countryCode']);
        $this->assertEquals('Lieferadressenzusatz', $shippingAddress['supplement']);
    }

    /** @test */
    public function it_can_create_a_company_contact(): void
    {
        // Prepare mock responses for company contact creation
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '87654321-abcd-1234-efgh-987654321987',
                'resourceUri' => 'https://api-sandbox.grld.eu/v1/contacts/87654321-abcd-1234-efgh-987654321987',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '87654321-abcd-1234-efgh-987654321987',
                'version' => 1,
                'roles' => [
                    'vendor' => ['number' => 'L-789'],
                ],
                'company' => [
                    'name' => 'Musterfirma GmbH',
                    'taxNumber' => 'DE123456789',
                    'vatRegistrationId' => 'DE987654321',
                    'allowTaxFreeInvoices' => false,
                    'contactPersons' => [
                        ['lastName' => 'Müller'],
                    ],
                ],
                'addresses' => [
                    ['billing' => [
                        [
                            'street' => 'Industriestraße 42',
                            'zip' => '54321',
                            'city' => 'Musterstadt',
                            'countryCode' => 'DE',
                        ],
                    ]],
                ],
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Firmenkontakt erstellen
        $contact = Contact::createCompany('Musterfirma GmbH');
        $contact->setAsVendor(['number' => 'L-789'])
            ->addBillingAddress(
                'Industriestraße 42',
                '54321',
                'Musterstadt',
                'DE'
            );

        // Kontakt speichern
        $savedContact = LexwareOffice::contacts()->create($contact);

        $this->assertEquals('87654321-abcd-1234-efgh-987654321987', $savedContact->getId());
        $this->assertEquals('Musterfirma GmbH', $savedContact->getCompany()->getName());
        $this->assertEquals('L-789', $savedContact->getRoles()['vendor']['number']);
        $billingAddress = $savedContact->getAddress('billing');
        $this->assertEquals('DE', $billingAddress['countryCode']);
    }
}
