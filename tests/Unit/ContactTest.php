<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['content']);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $result['content'][0]->getId());
        $this->assertEquals('223e4567-e89b-12d3-a456-426614174001', $result['content'][1]->getId());
        $this->assertEquals(0, $result['pagination']['page']);
        $this->assertEquals(25, $result['pagination']['size']);
        $this->assertEquals(1, $result['pagination']['totalPages']);
        $this->assertEquals(2, $result['pagination']['totalElements']);
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
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(3, $result['content']);
        $this->assertEquals(0, $result['pagination']['page']);
        $this->assertEquals(25, $result['pagination']['size']);
        $this->assertEquals(1, $result['pagination']['totalPages']);
        $this->assertEquals(3, $result['pagination']['totalElements']);
        
        // Test für Begrenzung der Ergebnisse pro Seite
        $result2 = $instance->contacts()->all(0, 10);
        $this->assertEquals(0, $result2['pagination']['page']);
        $this->assertEquals(10, $result2['pagination']['size']);
    }
}