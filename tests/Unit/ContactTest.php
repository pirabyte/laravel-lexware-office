<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ContactTest extends TestCase
{
    /** @test */
    public function it_can_create_a_contact()
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
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($instance, $client);

        // Kontaktdaten
        $contactData = [
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
        ];

        // API aufrufen
        $response = $instance->contacts->create($contactData);

        // Assertions
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $response['id']);
        $this->assertEquals(0, $response['version']);
    }
}