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
}