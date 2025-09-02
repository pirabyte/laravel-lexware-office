<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Classes\PaginatedResource;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Address;
use Pirabyte\LaravelLexwareOffice\Models\Company;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\ContactPerson;
use Pirabyte\LaravelLexwareOffice\Models\Person;
use Pirabyte\LaravelLexwareOffice\Models\XRechnung;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

/**
 * @property $app
 *
 * @method assertEquals(string $string, string $getId)
 */
class ContactTest extends TestCase
{
    /** @test */
    public function it_can_create_a_contact(): void
    {
        // Mock-Response erstellen
        $responseData = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 0,
            'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
            'updatedDate' => '2020-01-01T00:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Kontaktdaten
        $contactToCreate = Contact::fromArray([
            'version' => 0,
            'roles' => [
                'customer' => [
                    'number' => 'K-00001',
                ],
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
        ]);

        // API aufrufen
        $contact = $instance->contacts()->create($contactToCreate);

        // Assertions
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $contact->getId());
    }

    /** @test */
    public function it_can_update_a_contact(): void
    {
        // Mock-Response erstellen
        $getContactData = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 0,
            'roles' => [
                'customer' => [
                    'number' => 'K-00001',
                ],
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
            'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
            'updatedDate' => '2020-01-01T00:00:00.000+01:00',
        ];

        $updateResponseData = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 1,
            'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
            'updatedDate' => '2020-01-02T00:00:00.000+01:00',
        ];

        $updatedContactData = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 1,
            'roles' => [
                'customer' => [
                    'number' => 'K-00001',
                ],
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Maximilian',
                'lastName' => 'Mustermann',
            ],
            'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
            'updatedDate' => '2020-01-02T00:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($getContactData)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($updateResponseData)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($updatedContactData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Kontakt laden
        $contact = $instance->contacts()->get('123e4567-e89b-12d3-a456-426614174000');

        // Person aktualisieren - Vorname ändern
        $person = $contact->getPerson();
        $updatedPerson = Person::fromArray([
            'salutation' => $person->getSalutation(),
            'firstName' => 'Maximilian',
            'lastName' => $person->getLastName(),
        ]);

        // Reflection nutzen, um private setPerson Methode aufzurufen
        $reflectionClass = new \ReflectionClass($contact);
        $reflectionMethod = $reflectionClass->getMethod('setPerson');
        $reflectionMethod->invoke($contact, $updatedPerson);

        // Kontakt aktualisieren
        $updatedContact = $instance->contacts()->update($contact->getId(), $contact);

        // Assertions
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $updatedContact->getId());
        $this->assertEquals(1, $updatedContact->getVersion());
        $this->assertEquals('Maximilian', $updatedContact->getPerson()->getFirstName());
    }

    /** @test */
    public function it_can_filter_contacts(): void
    {
        // Mock-Response erstellen
        $contactsData = [
            'content' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00001',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann',
                    ],
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00002',
                        ],
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH',
                    ],
                ],
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 2,
            'totalPages' => 1,
            'numberOfElements' => 2,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Kontakte filtern
        $result = $instance->contacts()->filter([
            'customer' => true,
            'name' => 'Muster',
        ]);

        // Assertions
        $this->assertInstanceOf(PaginatedResource::class, $result);
        $resultArray = $result->jsonSerialize();
        $this->assertArrayHasKey('content', $resultArray);
        $this->assertCount(2, $resultArray['content']);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $resultArray['content'][0]->getId());
        $this->assertEquals('223e4567-e89b-12d3-a456-426614174001', $resultArray['content'][1]->getId());
        $this->assertEquals(0, $resultArray['number']);
        $this->assertEquals(25, $resultArray['size']);
        $this->assertEquals(1, $resultArray['totalPages']);
        $this->assertEquals(2, $resultArray['totalElements']);
        $this->assertEquals(2, $result->getTotal());
    }

    /** @test */
    public function it_can_get_all_contacts(): void
    {
        // Mock-Response erstellen
        $contactsData = [
            'content' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00001',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann',
                    ],
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'vendor' => [
                            'number' => 'L-00001',
                        ],
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH',
                    ],
                ],
                [
                    'id' => '323e4567-e89b-12d3-a456-426614174002',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00003',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Frau',
                        'firstName' => 'Erika',
                        'lastName' => 'Musterfrau',
                    ],
                ],
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 3,
            'totalPages' => 1,
            'numberOfElements' => 3,
        ];

        // Two responses for both method calls
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsData)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge($contactsData, ['size' => 10]))),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Alle Kontakte abrufen
        $result = $instance->contacts()->all();

        // Assertions
        $this->assertInstanceOf(PaginatedResource::class, $result);
        $resultArray = $result->jsonSerialize();
        $this->assertArrayHasKey('content', $resultArray);
        $this->assertCount(3, $resultArray['content']);
        $this->assertEquals(0, $resultArray['number']);
        $this->assertEquals(25, $resultArray['size']);
        $this->assertEquals(1, $resultArray['totalPages']);
        $this->assertEquals(3, $resultArray['totalElements']);

        // Test für Begrenzung der Ergebnisse pro Seite
        $result2 = $instance->contacts()->all(0, 10);
        $resultArray2 = $result2->jsonSerialize();
        $this->assertEquals(0, $resultArray2['number']);
        $this->assertEquals(10, $resultArray2['size']);
    }

    /** @test */
    public function it_can_only_extract_contact_count(): void
    {
        // Mock-Response erstellen
        $contactsData = [
            'content' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00001',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann',
                    ],
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'vendor' => [
                            'number' => 'L-00001',
                        ],
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH',
                    ],
                ],
                [
                    'id' => '323e4567-e89b-12d3-a456-426614174002',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00003',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Frau',
                        'firstName' => 'Erika',
                        'lastName' => 'Musterfrau',
                    ],
                ],
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 3,
            'totalPages' => 1,
            'numberOfElements' => 3,
        ];

        // Two responses for both method calls
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsData)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge($contactsData, ['size' => 10]))),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Alle Kontakte abrufen
        $count = $instance->contacts()->count();
        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_can_filter_out_empty_filter_values(): void
    {
        // Hier verwenden wir PHPUnit spies, um zu prüfen, dass der richtige Request gesendet wird
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'content' => [],
                'page' => 0,
                'size' => 25,
                'totalElements' => 0,
                'totalPages' => 0,
                'numberOfElements' => 0,
                'first' => true,
                'last' => true,
            ])),
        ]);

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Filter mit leeren Werten erstellen
        $instance->contacts()->filter([
            'name' => 'Test',
            'email' => '',
            'customer' => true,
            'vendor' => null,
            'number' => '12345',
        ]);

        // Assertions - Prüfen des tatsächlich gesendeten Requests
        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $query = \GuzzleHttp\Psr7\Query::parse($request->getUri()->getQuery());

        // Nur nicht-leere Filter sollten gesendet werden
        $this->assertArrayHasKey('name', $query);
        $this->assertArrayHasKey('customer', $query);
        $this->assertArrayHasKey('number', $query);
        $this->assertArrayNotHasKey('email', $query);
        $this->assertArrayNotHasKey('vendor', $query);

        // Werte prüfen
        $this->assertEquals('Test', $query['name']);
        $this->assertEquals('1', $query['customer']); // PHP kodiert bool true als '1' in query strings
        $this->assertEquals('12345', $query['number']);
    }

    /** @test */
    public function it_can_handle_duplicate_filter_values(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'content' => [],
                'page' => 0,
                'size' => 25,
                'totalElements' => 0,
                'totalPages' => 0,
                'numberOfElements' => 0,
                'first' => true,
                'last' => true,
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'content' => [],
                'page' => 0,
                'size' => 25,
                'totalElements' => 0,
                'totalPages' => 0,
                'numberOfElements' => 0,
                'first' => true,
                'last' => true,
            ])),
        ]);

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Da PHP selbst doppelte Array-Keys überschreibt, müssen wir das Array schrittweise aufbauen
        $instance->contacts()->filter([
            'name' => 'Test',
            'email' => 'first@example.com', // Wird durch API-Implementierung nicht überschrieben
        ]);

        // Assertions für den ersten Request
        $this->assertCount(1, $container);
        $request1 = $container[0]['request'];
        $query1 = \GuzzleHttp\Psr7\Query::parse($request1->getUri()->getQuery());

        // Prüfen der einzelnen Werte
        $this->assertEquals('Test', $query1['name']);
        $this->assertEquals('first@example.com', $query1['email']);

        // Array zurücksetzen
        $container = [];

        // Bei mehrfachen Aufrufen simulieren wir doppelte Filter
        $instance->contacts()->filter([
            'email' => ['first@example.com', 'second@example.com'],
        ]);

        // Assertions für den zweiten Request
        $this->assertCount(1, $container);
        $request2 = $container[0]['request'];
        $queryString = $request2->getUri()->getQuery();

        // Da Array-Parameter in der URI als email[0]=first@example.com&email[1]=second@example.com kodiert werden,
        // prüfen wir direkt den Query-String
        $this->assertStringContainsString('email%5B0%5D=first%40example.com', $queryString);
        $this->assertStringContainsString('email%5B1%5D=second%40example.com', $queryString);
    }

    /** @test */
    public function it_can_use_auto_paging_iterator(): void
    {
        // Zwei Seiten mit Kontakten simulieren
        $page1 = [
            'content' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00001',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann',
                    ],
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'vendor' => [
                            'number' => 'L-00001',
                        ],
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH',
                    ],
                ],
            ],
            'page' => 0,
            'size' => 2,
            'totalElements' => 3,
            'totalPages' => 2,
            'numberOfElements' => 2,
            'first' => true,
            'last' => false,
        ];

        $page2 = [
            'content' => [
                [
                    'id' => '323e4567-e89b-12d3-a456-426614174002',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00003',
                        ],
                    ],
                    'person' => [
                        'salutation' => 'Frau',
                        'firstName' => 'Erika',
                        'lastName' => 'Musterfrau',
                    ],
                ],
            ],
            'page' => 1,
            'size' => 2,
            'totalElements' => 3,
            'totalPages' => 2,
            'numberOfElements' => 1,
            'first' => false,
            'last' => true,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page1)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page2)),
        ]);

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // AutoPagingIterator verwenden
        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator(['name' => 'Muster'], 2) as $contact) {
            $contacts[] = $contact;
        }

        // Assertions
        $this->assertCount(3, $contacts);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $contacts[0]->getId());
        $this->assertEquals('223e4567-e89b-12d3-a456-426614174001', $contacts[1]->getId());
        $this->assertEquals('323e4567-e89b-12d3-a456-426614174002', $contacts[2]->getId());

        // Prüfen, dass zwei Anfragen gesendet wurden
        $this->assertCount(2, $container);

        // Prüfen der ersten Anfrage
        $request1 = $container[0]['request'];
        $query1 = \GuzzleHttp\Psr7\Query::parse($request1->getUri()->getQuery());
        $this->assertEquals('Muster', $query1['name']);
        $this->assertEquals('2', $query1['size']);
        $this->assertEquals('0', $query1['page']);

        // Prüfen der zweiten Anfrage (zweite Seite)
        $request2 = $container[1]['request'];
        $query2 = \GuzzleHttp\Psr7\Query::parse($request2->getUri()->getQuery());
        $this->assertEquals('Muster', $query2['name']);
        $this->assertEquals('2', $query2['size']);
        $this->assertEquals('1', $query2['page']);
    }

    public function test_it_can_deserialize_contacts_correctly(): void
    {
        $fixtureFile = __DIR__.'/../Fixtures/contacts/contact_serialization.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        $contact = Contact::fromArray($fixtureData);

        $this->assertEquals('be9475f4-ef80-442b-8ab9-3ab8b1a2aeb9', $contact->getId());
        $this->assertEquals(1, $contact->getVersion());

        $this->assertEquals(10307, $contact->getRoles()['customer']['number']);
        $this->assertEquals(70303, $contact->getRoles()['vendor']['number']);

        $this->assertEquals('Testfirma', $contact->getCompany()->getName());
        $this->assertEquals('12345/12345', $contact->getCompany()->getTaxNumber());
        $this->assertEquals('DE123456789', $contact->getCompany()->getVatRegistrationId());
        $this->assertTrue($contact->getCompany()->getAllowTaxFreeInvoices());

        $this->assertCount(1, $contact->getCompany()->getContactPersons());
        $contactPerson = $contact->getCompany()->getContactPersons()[0];
        $this->assertEquals('Herr', $contactPerson->getSalutation());
        $this->assertEquals('Max', $contactPerson->getFirstName());
        $this->assertEquals('Mustermann', $contactPerson->getLastName());
        $this->assertTrue($contactPerson->getPrimary());
        $this->assertEquals('contactpersonmail@lexware.de', $contactPerson->getEmailAddress());
        $this->assertEquals('08000/11111', $contactPerson->getPhoneNumber());

        $this->assertCount(1, $contact->getAddresses()['billing']);
        $billingAddress = $contact->getAddresses()['billing'][0];
        $this->assertEquals('Rechnungsadressenzusatz', $billingAddress->getSupplement());
        $this->assertEquals('Hauptstr. 5', $billingAddress->getStreet());
        $this->assertEquals('12345', $billingAddress->getZip());
        $this->assertEquals('Musterort', $billingAddress->getCity());
        $this->assertEquals('DE', $billingAddress->getCountryCode());

        $this->assertCount(1, $contact->getAddresses()['shipping']);
        $shippingAddress = $contact->getAddresses()['shipping'][0];
        $this->assertEquals('Lieferadressenzusatz', $shippingAddress->getSupplement());
        $this->assertEquals('Schulstr. 13', $shippingAddress->getStreet());
        $this->assertEquals('76543', $shippingAddress->getZip());
        $this->assertEquals('MUsterstadt', $shippingAddress->getCity());
        $this->assertEquals('DE', $shippingAddress->getCountryCode());

        $this->assertEquals('04011000-1234512345-35', $contact->getXRechnung()->getBuyerReference());
        $this->assertEquals('70123456', $contact->getXRechnung()->getVendorNumberAtCustomer());

        $this->assertEquals(['business@lexware.de'], $contact->getEmailAddresses()['business']);
        $this->assertEquals(['office@lexware.de'], $contact->getEmailAddresses()['office']);
        $this->assertEquals(['private@lexware.de'], $contact->getEmailAddresses()['private']);
        $this->assertEquals(['other@lexware.de'], $contact->getEmailAddresses()['other']);

        $this->assertEquals(['08000/1231'], $contact->getPhoneNumbers()['business']);
        $this->assertEquals(['08000/1232'], $contact->getPhoneNumbers()['office']);
        $this->assertEquals(['08000/1233'], $contact->getPhoneNumbers()['mobile']);
        $this->assertEquals(['08000/1234'], $contact->getPhoneNumbers()['private']);
        $this->assertEquals(['08000/1235'], $contact->getPhoneNumbers()['fax']);
        $this->assertEquals(['08000/1236'], $contact->getPhoneNumbers()['other']);

        $this->assertEquals('Notizen', $contact->getNote());
        $this->assertFalse($contact->getArchived());
    }

    /** @test */
    public function test_it_can_serialize_contacts_correctly(): void
    {
        $fixtureFile = __DIR__.'/../Fixtures/contacts/contact_serialization.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $expectedJson = json_decode($fixtureContents, true);

        $contact = new Contact;
        $contact->setId('be9475f4-ef80-442b-8ab9-3ab8b1a2aeb9');
        $contact->setOrganizationId('aa93e8a8-2aa3-470b-b914-caad8a255dd8');
        $contact->setVersion(1);
        $contact->setRoles([
            'customer' => ['number' => 10307],
            'vendor' => ['number' => 70303],
        ]);

        $company = new Company;
        $company->setName('Testfirma');
        $company->setTaxNumber('12345/12345');
        $company->setVatRegistrationId('DE123456789');
        $company->setAllowTaxFreeInvoices(true);

        $contactPerson = new ContactPerson;
        $contactPerson->setSalutation('Herr');
        $contactPerson->setFirstName('Max');
        $contactPerson->setLastName('Mustermann');
        $contactPerson->setPrimary(true);
        $contactPerson->setEmailAddress('contactpersonmail@lexware.de');
        $contactPerson->setPhoneNumber('08000/11111');
        $company->setContactPersons([$contactPerson]);
        $contact->setCompany($company);

        $billingAddress = new Address;
        $billingAddress->supplement = 'Rechnungsadressenzusatz';
        $billingAddress->street = 'Hauptstr. 5';
        $billingAddress->zip = '12345';
        $billingAddress->city = 'Musterort';
        $billingAddress->countryCode = 'DE';

        $shippingAddress = new Address;
        $shippingAddress->supplement = 'Lieferadressenzusatz';
        $shippingAddress->street = 'Schulstr. 13';
        $shippingAddress->zip = '76543';
        $shippingAddress->city = 'MUsterstadt';
        $shippingAddress->countryCode = 'DE';
        $contact->setAddresses(['billing' => [$billingAddress], 'shipping' => [$shippingAddress]]);

        $xRechnung = new XRechnung;
        $xRechnung->setBuyerReference('04011000-1234512345-35');
        $xRechnung->setVendorNumberAtCustomer('70123456');
        $contact->setXRechnung($xRechnung);

        $contact->setEmailAddresses([
            'business' => ['business@lexware.de'],
            'office' => ['office@lexware.de'],
            'private' => ['private@lexware.de'],
            'other' => ['other@lexware.de'],
        ]);

        $contact->setPhoneNumbers([
            'business' => ['08000/1231'],
            'office' => ['08000/1232'],
            'mobile' => ['08000/1233'],
            'private' => ['08000/1234'],
            'fax' => ['08000/1235'],
            'other' => ['08000/1236'],
        ]);

        $contact->setNote('Notizen');
        $contact->setArchived(false);

        $this->assertEquals($expectedJson, $contact->jsonSerialize());
    }

    public function test_setting_role_creates_an_empty_json_class(): void
    {
        $contact = Contact::createCompany('Musterfirma');
        $contact->setAsCustomer();
        $this->assertIsObject($contact->getRoles()['customer']);
        $json = json_encode($contact->jsonSerialize());
        $this->assertStringContainsString('"roles":{"customer":{}}', $json);
    }
}
