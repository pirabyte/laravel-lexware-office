<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ContactApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock-Responses für die API-Aufrufe
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000'
            ])),
            // Response für get
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'version' => 1,
                'roles' => [
                    'customer' => ['number' => 'K-12345']
                ],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann'
                ]
            ]))
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    /** @test */
    public function it_can_create_and_retrieve_a_contact()
    {
        // Kontakt erstellen
        $contact = LexwareOffice::contacts()->create([
            'version' => 0,
            'roles' => [
                'customer' => ['number' => 'K-12345']
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann'
            ]
        ]);

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $contact['id']);

        // Kontakt abrufen
        $retrievedContact = LexwareOffice::contacts()->get($contact['id']);

        $this->assertEquals('Herr', $retrievedContact['person']['salutation']);
        $this->assertEquals('Max', $retrievedContact['person']['firstName']);
        $this->assertEquals('Mustermann', $retrievedContact['person']['lastName']);
        $this->assertEquals('K-12345', $retrievedContact['roles']['customer']['number']);
    }
}