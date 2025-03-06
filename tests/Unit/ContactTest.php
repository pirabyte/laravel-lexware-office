<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Classes\PaginatedResource;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\Person;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

/**
 * @property $app
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
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseData))
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
                    'number' => 'K-00001'
                ]
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann'
            ]
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
                    'number' => 'K-00001'
                ]
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann'
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
                    'number' => 'K-00001'
                ]
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Maximilian',
                'lastName' => 'Mustermann'
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
            'lastName' => $person->getLastName()
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
                            'number' => 'K-00001'
                        ]
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann'
                    ]
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00002'
                        ]
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH'
                    ]
                ]
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 2,
            'totalPages' => 1,
            'numberOfElements' => 2
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsData))
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
            'name' => 'Muster'
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
                            'number' => 'K-00001'
                        ]
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann'
                    ]
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'vendor' => [
                            'number' => 'L-00001'
                        ]
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH'
                    ]
                ],
                [
                    'id' => '323e4567-e89b-12d3-a456-426614174002',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00003'
                        ]
                    ],
                    'person' => [
                        'salutation' => 'Frau',
                        'firstName' => 'Erika',
                        'lastName' => 'Musterfrau'
                    ]
                ]
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 3,
            'totalPages' => 1,
            'numberOfElements' => 3
        ];

        // Two responses for both method calls
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsData)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge($contactsData, ['size' => 10])))
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
                'last' => true
            ]))
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
            'number' => '12345'
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
                'last' => true
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'content' => [],
                'page' => 0,
                'size' => 25,
                'totalElements' => 0,
                'totalPages' => 0,
                'numberOfElements' => 0,
                'first' => true,
                'last' => true
            ]))
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
            'email' => 'first@example.com' // Wird durch API-Implementierung nicht überschrieben
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
            'email' => ['first@example.com', 'second@example.com']
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
                            'number' => 'K-00001'
                        ]
                    ],
                    'person' => [
                        'salutation' => 'Herr',
                        'firstName' => 'Max',
                        'lastName' => 'Mustermann'
                    ]
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'roles' => [
                        'vendor' => [
                            'number' => 'L-00001'
                        ]
                    ],
                    'company' => [
                        'name' => 'Musterfirma GmbH'
                    ]
                ]
            ],
            'page' => 0,
            'size' => 2,
            'totalElements' => 3,
            'totalPages' => 2,
            'numberOfElements' => 2,
            'first' => true,
            'last' => false
        ];
        
        $page2 = [
            'content' => [
                [
                    'id' => '323e4567-e89b-12d3-a456-426614174002',
                    'version' => 0,
                    'roles' => [
                        'customer' => [
                            'number' => 'K-00003'
                        ]
                    ],
                    'person' => [
                        'salutation' => 'Frau',
                        'firstName' => 'Erika',
                        'lastName' => 'Musterfrau'
                    ]
                ]
            ],
            'page' => 1,
            'size' => 2,
            'totalElements' => 3,
            'totalPages' => 2,
            'numberOfElements' => 1,
            'first' => false,
            'last' => true
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page1)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page2))
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
}